<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $query = Product::query()->with('categories');

        if ($request->filled('search')) {
            $query->where('product_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('product_categories.id', $request->category); // Fully qualified!
            });
        }

        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        if ($request->filled('price')) {
            $query->filterByPriceType($request->price);
        }

        $products = $query->latest()->paginate(10)->withQueryString(); // keeps filters in pagination links

        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            return view('admin.product_management.products.partials._table', compact('products'))->render();
        }

        $categories = ProductCategory::orderBy('title')->get();

        return view('admin.product_management.products.index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}

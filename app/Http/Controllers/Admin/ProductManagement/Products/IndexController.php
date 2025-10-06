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
        $query = Product::query()->select('products.*')->leftJoin('product_category_children', 'products.id', '=', 'product_category_children.product_id')->leftJoin('product_categories', 'product_category_children.product_category_id', '=', 'product_categories.id')->with('categories'); // eager load if needed for display

        if ($request->filled('search')) {
            $query->where('product_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('product_categories.id', $request->category);
            });
        }

        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        if ($request->filled('price')) {
            $query->filterByPriceType($request->price);
        }

        // Order by category title and product_name
        $products = $query->orderBy('product_categories.title', 'asc')->orderBy('products.product_name', 'asc')->paginate(10)->withQueryString();

        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            return view('admin.product_management.products.partials._table', compact('products'))->render();
        }

        $categories = ProductCategory::getHierarchy();

        return view('admin.product_management.products.index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}

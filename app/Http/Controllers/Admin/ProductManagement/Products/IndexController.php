<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use Illuminate\Support\Facades\DB;
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
        $query = Product::query()->select('products.*')->with('categories'); // keep eager loading for display

        // ---- subquery: one category title per product (for ordering only) ----
        $categoryTitleSub = DB::table('product_category_children as pcc')->join('product_categories as pc', 'pc.id', '=', 'pcc.product_category_id')->select('pcc.product_id', DB::raw('MIN(pc.title) as first_category_title'))->groupBy('pcc.product_id');

        // join the subquery (does NOT multiply rows)
        $query->leftJoinSub($categoryTitleSub, 'ct', function ($join) {
            $join->on('ct.product_id', '=', 'products.id');
        });

        // ---- filters (these do not cause duplication) ----
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

        // ---- ordering: by derived category title then product name ----
        // If you want products without categories LAST, use the commented line.
        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

        $products = $query
            ->orderBy('ct.first_category_title', 'asc') // or:
            // ->orderByRaw('ct.first_category_title IS NULL, ct.first_category_title ASC')
            ->orderBy('products.product_name', 'asc')
            ->paginate($perPageVal)
            ->withQueryString();

        // AJAX partial
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

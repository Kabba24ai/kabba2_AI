<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;

// Models
use App\Models\ProductManagement\Product;

class ProductSearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($search, $currentProductId = null)
    {
        $search = trim($search);
        $products = Product::with('media')
            ->where('id', '!=', $currentProductId)
            ->when($search, fn($qb) => $qb->where('product_name', 'like', "%{$search}%"))
            ->limit(5)
            ->get();

        return response()->json($products);
    }
}

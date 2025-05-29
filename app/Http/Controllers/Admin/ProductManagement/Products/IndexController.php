<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models

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
        // Create an empty collection
        $items = Collection::make([]);

        // Set pagination parameters
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Slice the empty collection (though it's empty)
        $currentItems = $items->slice($offset, $perPage)->values();

        // Create paginator
        $products = new LengthAwarePaginator($currentItems, $items->count(), $perPage, $currentPage, ['path' => request()->url(), 'query' => request()->query()]);

        return view('admin.product_management.products.index', [
            'products' => $products,
        ]);
    }
}

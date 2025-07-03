<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

// Models
use App\Models\ProductManagement\Product;

class DeleteController extends Controller
{
    /**
     * Handle the request to delete a product .
     *
     * @param  string  $unique_id
     * @return RedirectResponse
     */
    public function __invoke(string $unique_id): RedirectResponse
    {
        $objProduct = Product::where('unique_id', $unique_id)->firstOrFail();

        // Delete the main
        $objProduct->delete();

        flash('Product deleted successfully.')->success();

        return redirect()->route('admin.product-management.products.index');
    }
}

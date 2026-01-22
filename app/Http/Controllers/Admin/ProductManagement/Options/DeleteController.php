<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductOption;
use Illuminate\Http\RedirectResponse;

class DeleteController extends Controller
{
    /**
     * Handle the request to delete a product option.
     *
     * @param  string  $unique_id
     * @return RedirectResponse
     */
    public function __invoke(string $unique_id): RedirectResponse
    {
        $productOption = ProductOption::where('unique_id', $unique_id)->firstOrFail();

        // Delete related option rows if applicable
        $productOption->items()->delete();

        // Delete the main option
        $productOption->delete();

        flash('Product Option deleted successfully.')->success();

        return redirect()->route('admin.product-management.options.index');
    }
}

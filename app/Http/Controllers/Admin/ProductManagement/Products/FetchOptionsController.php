<?php

namespace App\Http\Controllers\Admin\ProductManagement\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\ProductManagement\ProductOption;

class FetchOptionsController extends Controller
{
    /**
     * Return JSON payload of one or more options and their items.
     *
     * Expects query param: ?ids[]=1&ids[]=2...
     */
    public function __invoke($optionId)
    {
        $objProductOption = ProductOption::where('id', $optionId)->firstOrFail();

        // Render the Blade component to HTML
        $html = view('components.admin.product-management.products.option-preview', [
            'objProductOption' => $objProductOption,

        ])->render();

        return response()->json(['html' => $html]);
    }
}

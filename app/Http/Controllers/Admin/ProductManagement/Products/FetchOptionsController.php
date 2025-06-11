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
    public function __invoke(Request $request)
    {
        $opts = ProductOption::whereIn('id', $request->input('options', []))->get();

        // Render the Blade component to HTML
        $html = view('components.admin.product-management.products.option-preview', [
            'options' => $opts,
            'productType' => $productType = $request->input('product_type', 'retail'),

        ])->render();

        return response()->json(['html' => $html]);
    }
}

<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use App\Services\DocumentGenerator\DocumentGenerator;
use App\Services\DocumentGenerator\Documents\CustomerPriceListDocument;
use Illuminate\Http\Request;

class PriceListGenerateController extends Controller
{
    /**
     * Business module owns the action; the Document Generator owns the
     * presentation. GET so the printable document opens in a new tab and
     * can be refreshed for current pricing.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:product_categories,id'],
        ], [
            'category_ids.required' => 'Select at least one category for the price list.',
        ]);

        return DocumentGenerator::make(CustomerPriceListDocument::KEY)
            ->render(['category_ids' => $validated['category_ids']]);
    }
}

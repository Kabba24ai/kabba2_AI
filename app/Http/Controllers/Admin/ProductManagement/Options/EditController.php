<?php

namespace App\Http\Controllers\Admin\ProductManagement\Options;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductOption;
use Illuminate\View\View;

class EditController extends Controller
{
    /**
     * Show the form for editing the specified product option.
     *
     * @param string $unique_id
     * @return View
     */
    public function __invoke(string $unique_id): View
    {
        // Load main option with related pricing options if applicable
        $objProductOption = ProductOption::with(['items', 'groups.items', 'groups.media'])->where('unique_id', $unique_id)->firstOrFail();

        return view('admin.product_management.options.edit', [
            'objProductOption' => $objProductOption,
        ]);
    }
}

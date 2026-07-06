<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Http\Controllers\Controller;
use App\Models\Documents\PriceListPreset;

class IndexController extends Controller
{
    /** All presets (active and inactive) for admin management. */
    public function __invoke()
    {
        $presets = PriceListPreset::query()
            ->withCount('categories')
            ->ordered()
            ->get();

        return view('admin.documents.presets.index', compact('presets'));
    }
}

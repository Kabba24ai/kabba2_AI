<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Documents\SavePriceListPresetRequest;
use App\Models\Documents\PriceListPreset;

class StoreController extends Controller
{
    public function __invoke(SavePriceListPresetRequest $request)
    {
        $validated = $request->validated();

        $preset = PriceListPreset::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'sort_order'  => $validated['sort_order'] ?? null,
        ]);

        $preset->categories()->sync($validated['category_ids']);

        flash('Preset "' . $preset->name . '" created.')->success();

        return redirect()->route('admin.documents.presets.index');
    }
}

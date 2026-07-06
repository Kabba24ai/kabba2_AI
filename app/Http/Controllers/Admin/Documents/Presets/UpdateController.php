<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Documents\SavePriceListPresetRequest;
use App\Models\Documents\PriceListPreset;

class UpdateController extends Controller
{
    public function __invoke(SavePriceListPresetRequest $request, PriceListPreset $preset)
    {
        $validated = $request->validated();

        $preset->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'sort_order'  => $validated['sort_order'] ?? null,
        ]);

        $preset->categories()->sync($validated['category_ids']);

        if ($request->hasFile('thumbnail')) {
            $upload = MediaHelper::uploadStorageFile('Public Asset', $request->file('thumbnail'), 'price_list_presets', $preset);
            $preset->update(['media_id' => $upload['mediaObj']->id]);
        } elseif ($request->boolean('remove_thumbnail')) {
            $preset->update(['media_id' => null]);
        }

        flash('Preset "' . $preset->name . '" updated.')->success();

        return redirect()->route('admin.documents.presets.index');
    }
}

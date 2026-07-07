<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Helpers\ImageHelper;
use App\Helpers\MediaHelper;
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

        if ($request->hasFile('thumbnail')) {
            // Center-crop/resize to a 500px square; cards display it at 250px
            $thumbnail = ImageHelper::squareThumbnail($request->file('thumbnail'), 500);
            $upload = MediaHelper::uploadStorageFile('Public Asset', $thumbnail, 'price_list_presets', $preset);
            $preset->update(['media_id' => $upload['mediaObj']->id]);
        }

        flash('Preset "' . $preset->name . '" created.')->success();

        return redirect()->route('admin.documents.presets.index');
    }
}

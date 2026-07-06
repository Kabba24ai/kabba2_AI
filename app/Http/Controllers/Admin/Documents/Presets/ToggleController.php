<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Http\Controllers\Controller;
use App\Models\Documents\PriceListPreset;

class ToggleController extends Controller
{
    /** Quick activate/deactivate from the preset list. */
    public function __invoke(PriceListPreset $preset)
    {
        $preset->update(['is_active' => !$preset->is_active]);

        flash('Preset "' . $preset->name . '" ' . ($preset->is_active ? 'activated' : 'deactivated') . '.')->success();

        return redirect()->route('admin.documents.presets.index');
    }
}

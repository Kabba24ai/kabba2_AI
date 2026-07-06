<?php

namespace App\Http\Controllers\Admin\Documents\Presets;

use App\Http\Controllers\Controller;
use App\Models\Documents\PriceListPreset;

class DestroyController extends Controller
{
    /**
     * Soft delete (project convention). Always safe: presets are a selection
     * convenience — no generated document or other record references them.
     */
    public function __invoke(PriceListPreset $preset)
    {
        $preset->delete();

        flash('Preset "' . $preset->name . '" deleted.')->success();

        return redirect()->route('admin.documents.presets.index');
    }
}

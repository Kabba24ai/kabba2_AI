<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dashboard\FuelNotePreset;
use Illuminate\Http\Request;

class FuelNotePresetController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255|unique:fuel_note_presets,label',
        ]);

        $preset = FuelNotePreset::create(['label' => trim($request->label)]);

        return response()->json(['success' => true, 'preset' => $preset]);
    }
}

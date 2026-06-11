<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dashboard\ResolutionNotePreset;
use Illuminate\Http\Request;

class ResolutionPresetController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100|unique:resolution_note_presets,label',
        ]);

        $preset = ResolutionNotePreset::create(['label' => trim($request->label)]);

        return response()->json(['success' => true, 'preset' => $preset]);
    }

    public function destroy($id)
    {
        ResolutionNotePreset::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}

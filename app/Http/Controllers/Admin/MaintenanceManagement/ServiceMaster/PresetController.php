<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\IntervalPreset;
use Illuminate\Http\Request;

class PresetController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'intervals' => 'required|array|min:1',
            'intervals.*' => 'required|integer|min:1'
        ]);
        
        $preset = IntervalPreset::create([
            'name' => $request->name,
            'description' => $request->description,
            'intervals' => array_unique(array_map('intval', $request->intervals))
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Interval preset created successfully',
            'preset' => $preset
        ]);
    }
    
    public function destroy($id)
    {
        $preset = IntervalPreset::findOrFail($id);
        
        // Check if preset is being used by templates
        if ($preset->templates()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete preset that is being used by templates'
            ], 409);
        }
        
        $preset->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Interval preset deleted successfully'
        ]);
    }
}
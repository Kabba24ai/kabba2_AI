<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Preset;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\IntervalPreset;

class DestroyController extends Controller
{
    public function __invoke($id)
    {
        $preset = IntervalPreset::findOrFail($id);
        
        // Check if preset is being used by any templates
        if ($preset->templates()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete preset. It is currently being used by one or more templates.',
            ], 422);
        }

        $preset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Interval preset deleted successfully',
        ]);
    }
}

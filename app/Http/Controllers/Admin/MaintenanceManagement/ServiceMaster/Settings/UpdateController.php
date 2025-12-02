<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Settings\UpdateRequest;
use App\Models\MaintenanceManagement\ServiceMasterSettings;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request)
    {
        $validated = $request->validated();

        // Get or create settings (should only be one record)
        $settings = ServiceMasterSettings::firstOrNew([]);
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'message' => 'Settings updated successfully',
        ]);
    }
}

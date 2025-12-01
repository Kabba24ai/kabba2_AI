<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMasterSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'pending_before_hours' => 'required|integer|min:1|max:720',
            'pending_after_hours' => 'required|integer|min:1|max:720',
            'master_admin_code' => 'nullable|string|max:50'
        ]);
        
        $settings = ServiceMasterSettings::firstOrCreate([]);
        
        $settings->update([
            'pending_before_hours' => $request->pending_before_hours,
            'pending_after_hours' => $request->pending_after_hours,
            'master_admin_code' => $request->master_admin_code
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'settings' => $settings
        ]);
    }
}
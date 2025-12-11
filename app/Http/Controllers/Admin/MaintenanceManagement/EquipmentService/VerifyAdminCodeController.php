<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifyAdminCodeController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'admin_code' => 'required|string',
        ]);

        $settings = DB::table('service_master_settings')->first();
        
        if (!$settings || !$settings->master_admin_code) {
            return response()->json([
                'success' => false,
                'message' => 'Master admin code not configured',
            ], 400);
        }

        if ($request->admin_code === $settings->master_admin_code) {
            return response()->json([
                'success' => true,
                'message' => 'Admin code verified',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid admin code',
        ], 401);
    }
}

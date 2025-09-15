<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;

// Requests

use Illuminate\Http\Request;

// Models
use App\Models\Configurations\Setting;

class VerifyMasterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'field_id' => 'required|integer',
        ]);

        $master = Setting::where('setting_name', 'master_passcode')->value('setting_value');

        if ($request->password === $master) {
            return response()->json([
                'status' => 'success',
                'field_id' => $request->field_id,
                'message' => 'Master password verified. Field unlocked.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Wrong master password.',
        ], 403);
    }
}

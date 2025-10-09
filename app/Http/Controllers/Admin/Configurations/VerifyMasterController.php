<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // <-- import logger
use App\Models\Configurations\Setting;

class VerifyMasterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            
        ]);

        $master = Setting::where('setting_name', 'master_passcode')->first()?->setting_value;

        if ($request->password === $master) {
           

            return response()->json([
                'ok' => true,
                'status' => 'success',
                
                'message' => 'Master password verified. Field unlocked.',
            ]);
        }

     

        return response()->json([
            'ok' => false,
            'status' => 'error',
            'message' => 'Wrong master password.',
        ], 403);
    }
}

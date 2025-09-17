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
            'field_id' => 'required|integer',
        ]);

        $master = Setting::where('setting_name', 'master_passcode')->first()?->setting_value;

        if ($request->password === $master) {
            //  Log success
            // Log::info('Master password verified', [
            //     'user_id' => auth()->id(),
            //     'field_id' => $request->field_id,
            //     'ip'       => $request->ip(),
            //     'master' => $master,
            // ]);

            return response()->json([
                'status' => 'success',
                'field_id' => $request->field_id,
                'message' => 'Master password verified. Field unlocked.',
            ]);
        }

        // ❌ Log failure
        // Log::warning('Failed master password attempt', [
        //     'user_id' => auth()->id(),
        //     'field_id' => $request->field_id,
        //     'ip'       => $request->ip(),
        //     'attempted_password' => $request->password, // remove if too sensitive
        //     'master' => $master,

        // ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Wrong master password.',
        ], 403);
    }
}

<?php

namespace App\Http\Controllers\Admin\Hrm\Users;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Iam\Personnel\User;
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\Crypt;

class VerifySsnController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $master = Setting::where('setting_name', 'master_password_entry')->first();
        $decryptedMaster = $master ? $master->getDecryptedSettingValue() : null;

        if ($decryptedMaster !== $request->password) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong master password.'
            ], 403);
        }

         $decryptedSsn = null;

        if ($user->social_security) {
            try {
                $decryptedSsn = Crypt::decryptString($user->social_security);
            } catch (\Exception $e) {
                $decryptedSsn = null;
            }
        }

        return response()->json([
            'success' => true,
            'ssn' => $decryptedSsn, // original SSN
        ]);
    }
}


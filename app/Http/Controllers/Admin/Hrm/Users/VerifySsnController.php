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

        if (!$user->social_security) {
            return response()->json([
                'success'     => true,
                'ssn'         => null,
                'ssn_missing' => true,
            ]);
        }

        try {
            $decryptedSsn = Crypt::decryptString($user->social_security);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to decrypt SSN. The stored value may be corrupted.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'ssn'     => $decryptedSsn,
        ]);
    }
}


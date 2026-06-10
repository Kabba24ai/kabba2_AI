<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;

class ToggleDriverController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $user = User::where('unique_id', $unique_id)->firstOrFail();

        $user->update(['is_driver' => (bool) $request->boolean('is_driver')]);

        return response()->json(['success' => true, 'is_driver' => $user->is_driver]);
    }
}

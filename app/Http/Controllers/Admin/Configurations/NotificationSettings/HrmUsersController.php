<?php

namespace App\Http\Controllers\Admin\Configurations\NotificationSettings;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;

class HrmUsersController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $users = User::query()
            ->active()
            ->with(['roles:id,name,color']) //  load roles
            ->select('id', 'first_name', 'last_name', 'mobile_phone')
            ->whereNotNull('mobile_phone')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'users' => $users->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => trim($u->first_name . ' ' . $u->last_name),
                'phone' => $u->mobile_phone,

                //  role data
                'roles' => $u->roles->map(fn ($r) => [
                    'name'  => $r->name,
                    'color' => $r->color,
                ])->values(),
            ])->values()
        ]);
    }
}

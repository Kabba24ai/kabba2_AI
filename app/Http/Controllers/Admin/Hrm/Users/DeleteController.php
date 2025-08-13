<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\Locations\State;
use App\Models\Iam\Personnel\User;



class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($unique_id)
        {
        try {
                $user = User::where('unique_id', $unique_id)->firstOrFail();

                // Clean up roles, permissions, emergency contacts
                $user->syncRoles([]);
                $user->syncPermissions([]);
                $user->emergencyContacts()->delete();

                $user->delete();

                return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['success' => false, 'message' => 'Failed to delete user.'], 500);
            }
        }

}

<?php

namespace App\Http\Controllers\Admin\Hrm\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Iam\AccessControl\Role;


class DeleteRoleController extends Controller
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
                $Role = Role::where('unique_id', $unique_id)->firstOrFail();

                $Role->delete();

                return response()->json(['success' => true, 'message' => 'Role deleted successfully.']);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['success' => false, 'message' => 'Failed to delete Role.'], 500);
            }
        }

}

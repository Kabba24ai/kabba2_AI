<?php

namespace App\Http\Controllers\Admin\Hrm\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models

use App\Models\Iam\AccessControl\Role;


class ManageRoleController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

       
    $roles = Role::withCount('users', 'permissions')->get();


    return view('admin.hrm.roles.manage-role', compact('roles'));
    }
}

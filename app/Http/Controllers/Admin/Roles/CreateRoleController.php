<?php

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\Iam\AccessControl\Permission;
use App\Models\Iam\AccessControl\Module;


class CreateRoleController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
       $modules = Module::with('permissions')->get();
       return view('admin.roles.create-role', compact('modules'));
    }
}

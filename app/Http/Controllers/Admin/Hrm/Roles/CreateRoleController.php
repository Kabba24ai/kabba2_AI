<?php

namespace App\Http\Controllers\Admin\Hrm\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Configurations\Color;

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
            $colors = Color::all(); // Fetch all color records

       $modules = Module::with('permissions')->get();
       return view('admin.hrm.roles.create-role', compact('modules','colors'));
    }
}

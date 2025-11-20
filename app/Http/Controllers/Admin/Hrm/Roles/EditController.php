<?php

namespace App\Http\Controllers\Admin\Hrm\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Locations\State;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\AccessControl\Role;
use App\Models\Iam\AccessControl\Module;

use App\Models\Configurations\Color;

class EditController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request,$unique_id)
    {

        $role = Role::where('unique_id',$unique_id)->first();
        $modules = Module::with('permissions')->get();
            $colors = Color::all(); // Fetch all color records


        // dd($role);

        return view('admin.hrm.roles.edit', [
            'modules' => $modules,
             'role' => $role,
            'colors' => $colors,
        ]);
    }
}

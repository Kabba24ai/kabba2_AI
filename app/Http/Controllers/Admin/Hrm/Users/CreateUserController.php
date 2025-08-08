<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Locations\State;

use App\Models\Iam\AccessControl\Role;

class CreateUserController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

         $states = State::get();

         $roles = Role::get();

        return view('admin.hrm.users.create-user', [
            'states' => $states,
            'roles' => $roles,
        ]);
    }
}

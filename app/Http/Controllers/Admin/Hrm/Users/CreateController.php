<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Locations\State;

use App\Models\Iam\AccessControl\Role;
use App\Enums\UserPayType;



class CreateController extends Controller
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

         $paytypes = UserPayType::options();

        return view('admin.hrm.users.create', [
            'states' => $states,
            'roles' => $roles,
            'paytypes' => $paytypes,
        ]);
    }
}

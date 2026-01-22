<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Locations\State;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\AccessControl\Role;
use App\Enums\UserPayType;


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

         $states = State::get();

        // Fetch the user data based on unique_id or any other identifier

        $user = User::where('unique_id', $unique_id)->first();

        $roles = Role::get();
        $paytypes = UserPayType::options();


        return view('admin.hrm.users.edit', [
            'states' => $states,
            'user' => $user,
            'roles' => $roles,
            'paytypes' => $paytypes,

        ]);
    }
}

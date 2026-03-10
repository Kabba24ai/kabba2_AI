<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\Locations\State;
use App\Models\Iam\Personnel\User;


use App\Enums\UserPayType;


class ViewController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke($unique_id)
    {
          $states = State::get();

        // Fetch the user data based on unique_id or any other identifier

        $user = User::with('roles')->where('unique_id', $unique_id)->first();
        $paytypes = UserPayType::options();

         return view('admin.hrm.users.view', [
            'states' => $states,
            'user' => $user,
            'paytypes' => $paytypes,
        ]);

    }
}

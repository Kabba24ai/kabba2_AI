<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\AccessControl\Role;
use App\Enums\UserPayType;

// Models


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

    //    $users = User::with('roles')->get();
       $query = User::with('roles')->orderBy('first_name');

      // Name filter
    if ($request->filled('search_name')) {
        $query->where(function ($q) use ($request) {
            $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->search_name}%"])
              ->orWhere('email', 'like', '%' . $request->search_name . '%');
        });
    }
      // Status filter
    if ($request->filled('user_status') && in_array(strtolower($request->user_status), ['active', 'inactive'])) {
        $query->where('status', ucfirst(strtolower($request->user_status)));
    }


     $users = $query->get();
       $roles = Role::get();

     $paytypes = UserPayType::options();



        // If AJAX, return partial
    if ($request->ajax()) {
        return response()->json([
            'html' => view('admin.hrm.users.partials._user_cards', compact('users'))->render(),
            'total' => $users->count(),
            'paytypes' => $paytypes,
        ]);
    }

    return view('admin.hrm.users.index', compact('users', 'roles','paytypes'));
    }
}

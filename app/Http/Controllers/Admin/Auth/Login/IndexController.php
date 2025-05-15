<?php

namespace App\Http\Controllers\Admin\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        if (Auth::check()) {
            $user = auth()->user();

            if ($user->type == 'Tournament') {
                return redirect(route('admin.customers.pending'));
            }

            if ($user->type == 'Report') {
                return redirect(route('admin.cash-reports.index'));
            }
            return redirect(route('admin.promotions.index'));
        }

        return view('admin.auth.login.index', []);
    }
}

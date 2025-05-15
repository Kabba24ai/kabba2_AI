<?php

namespace App\Http\Controllers\Admin\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// Request
use App\Http\Requests\Admin\Auth\Login\PostRequest;
use App\Models\User;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
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

        // Validate the email and password
        $credentials = $request->only('email', 'password');

        try {
            // // Find the user by email to determine the role
            // $user = User::where('email', $credentials['email'])->first();

            // // Check if user exists
            // if (!$user) {
            //     throw new \Exception('No account found with that email.');
            // }

            // // Determine the appropriate guard based on the user's role
            // $guard = null;
            // if ($user->type === 'Admin') {
            //     $guard = 'admin'; // Use the 'admin' guard
            // } elseif ($user->type === 'Cashier') {
            //     $guard = 'cashier'; // Use the 'cashier' guard
            // } else {
            //     throw new \Exception('This user role is not allowed to log in.');
            // }

            // Attempt login with the selected guard
            if (Auth::attempt($credentials)) { //guard($guard)->
                // If successful, redirect to the admin dashboard
                $user = auth()->user();
                if ($user->type == 'Tournament') {
                    return redirect(route('admin.customers.pending'));
                }

                if ($user->type == 'Report') {
                    return redirect(route('admin.cash-reports.index'));
                }
                return redirect(route('admin.promotions.index'));
            } else {
                // If authentication fails, redirect back with an error message
                return redirect(route('admin.auth.login'))
                    ->withInput($request->only('email'))
                    ->withErrors([
                        'email' => 'The provided credentials are incorrect.',
                    ]);
            }
        }catch (\Exception $e) {
            Auth::logout();
            return redirect(route('admin.auth.login'))
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => $e->getMessage(),
                ]);
        }

        return redirect(route('admin.promotions.index'));
    }


}

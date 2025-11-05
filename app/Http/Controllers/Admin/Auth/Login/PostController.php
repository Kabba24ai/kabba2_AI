<?php

namespace App\Http\Controllers\Admin\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Iam\Personnel\User;


// Request
use App\Http\Requests\Admin\Auth\Login\PostRequest;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
    {

        if (Auth::check()) {
            $user = auth()->user();

            return redirect(route('admin.dashboard.index'));
        }

        // Validate the email and password
        $credentials = $request->only('email', 'password');

        try {

             // Find user by email
            $user = User::where('email', $credentials['email'])->first();

            // Check user existence
            if (!$user) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'No account found with that email address.']);
            }

            //  Check if user is inactive
            if (isset($user->status) && $user->status === 'Inactive') {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Your account is inactive. Please contact the administrator.']);
            }

            // Attempt login with the selected guard
            if (Auth::guard('web')->attempt($credentials)) {
                // If successful, redirect to the admin dashboard
                $user = auth()->user();

                return redirect(route('admin.dashboard.index'));
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

        return redirect(route('admin.dashboard.index'));
    }

}

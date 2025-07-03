<?php

namespace App\Http\Controllers\Admin\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

<?php

use Illuminate\Support\Facades\Route;

Route::name('auth.')->group(function () {

    // Register
    require base_path('routes/front/auth/login/routes.php');

    // Login
    require base_path('routes/front/auth/register/routes.php');

    // Logout
    require base_path('routes/front/auth/logout/routes.php');

    // Reset password
    require base_path('routes/front/auth/reset_password/routes.php');
});

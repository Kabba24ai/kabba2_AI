<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Front Routes
|--------------------------------------------------------------------------
|
*/

Route::name('front.')->group(function () {

    Route::middleware('front.common-front-data','web')->group(function () {
        // Home
        require base_path('routes/front/home/routes.php');

        // Categories
        require base_path('routes/front/categories/routes.php');

        // Products
        require base_path('routes/front/products/routes.php');

        // Faqs
        require base_path('routes/front/faqs/routes.php');

        // Contact us
        require base_path('routes/front/contact_us/routes.php');

        // Privacy policy
        require base_path('routes/front/privacy_policy/routes.php');

        // Terms and Conditions
        require base_path('routes/front/terms_and_conditions/routes.php');

        // Checkout
        require base_path('routes/front/checkout/routes.php');

        // Cart
        require base_path('routes/front/cart/routes.php');

        // Customer Auth
        require base_path('routes/front/auth/routes.php');
    });

    Route::prefix('customer')->middleware(['web', 'auth:customer','front.common-front-data'])->name('customer.')->group(function ($router) {
        // dashboard
         require base_path('routes/front/customer/dashboard/routes.php');
        // profile
         require base_path('routes/front/customer/profile/routes.php');
        // orders
        require base_path('routes/front/customer/orders/routes.php');
    });
});


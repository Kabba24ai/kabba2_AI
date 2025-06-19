<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Front Routes
|--------------------------------------------------------------------------
|
*/

        // Public routes (e.g., login, register)
        require base_path('routes/front/auth/routes.php');

        // Pages (e.g., home, about, contact)
        require base_path('routes/front/pages/routes.php');

        // Product Management
        require base_path('routes/front/product_management/category/routes.php');
        require base_path('routes/front/product_management/order/routes.php');
        require base_path('routes/front/product_management/product/routes.php');

        // Terms and Conditions
        require base_path('routes/front/terms_and_conditions/routes.php');
  

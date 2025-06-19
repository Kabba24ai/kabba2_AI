<?php

use Illuminate\Support\Facades\Route;


Route::prefix('customer')->name('customer.')->group(function () {

    // Orders
    require base_path('routes/front/customer/orders/routes.php');
});

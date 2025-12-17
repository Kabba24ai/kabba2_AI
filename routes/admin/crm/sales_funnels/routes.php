<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\SalesFunnels\IndexController;


Route::prefix('sales-funnels')
->name('sales-funnels.')
->group(function () {
    Route::get('/', IndexController::class)->name('index');

    require base_path('routes/admin/crm/sales_funnels/categories/routes.php');

});

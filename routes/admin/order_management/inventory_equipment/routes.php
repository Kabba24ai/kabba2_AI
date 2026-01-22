<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\InventoryEquipment\IndexController;

Route::prefix('inventory-equipment')
    ->name('inventory-equipment.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');

       
});

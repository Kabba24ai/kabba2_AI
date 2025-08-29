<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\EquipmentInventory\IndexController;

Route::prefix('equipment-inventory')
    ->name('equipment-inventory.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');

       
});

<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\DeleteController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\CopyController;



Route::prefix('templates')
->name('templates.')
->group(function ($router) {

    // CLEAN-5 (Phase 3 housekeeping): the GET / "index" route pointed at a view
    // that never existed on disk (would throw ViewNotFoundException if ever hit)
    // and was never linked from any nav/tab. The live Templates UI is the
    // tab-based partial on the Rental Ready index page. Removed; see
    // docs/checklist-system-audit/P3_4_HOUSEKEEPING.md.

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');

    Route::post('/{unique_id}/copy', CopyController::class)->name('copy');

    
});

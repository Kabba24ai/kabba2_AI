<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\DeleteController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\CopyController;



Route::prefix('templates')
->name('templates.')
->group(function ($router) {

    // CLEAN-1 (Phase 3 housekeeping): the GET / "index" route + its controller/view
    // were an orphaned mockup — never linked from any nav/tab, hardcoded fake data,
    // and the live Templates UI is the tab-based partial on the Customer Admin index
    // page. Removed; see docs/checklist-system-audit/P3_4_HOUSEKEEPING.md.

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');

    Route::post('/{unique_id}/copy', CopyController::class)->name('copy');

    
});

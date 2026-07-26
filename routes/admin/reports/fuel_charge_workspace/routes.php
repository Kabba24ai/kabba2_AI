<?php

use Illuminate\Support\Facades\Route;

// RELOCATED — the Fuel Charge Workspace now lives in Task Manager →
// Billing Operations (admin.tasks.billing.fuel-charges.index). This legacy
// URL is kept as a permanent redirect so existing bookmarks and older links
// do not fail. The route NAME is also preserved so any missed reference
// still resolves; there is no second copy of the page.
Route::prefix('fuel-charge-workspace')->name('fuel-charge-workspace.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.tasks.billing.fuel-charges.index'))->name('index');
});

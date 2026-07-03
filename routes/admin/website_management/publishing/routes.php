<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish\PublishController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish\UnpublishController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish\ArchiveController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish\SaveDraftController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish\AutoSaveController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision\IndexController    as RevisionIndexController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision\ShowController     as RevisionShowController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision\CompareController  as RevisionCompareController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision\RestoreController  as RevisionRestoreController;
use App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision\DeleteController   as RevisionDeleteController;

/*
 * All publishing routes are scoped to a specific page via {page_unique_id}.
 * Route names: admin.website-management.pages.*
 *
 * Usage from any page editor:
 *   route('admin.website-management.pages.publish', $page->unique_id)
 */
Route::prefix('pages/{page_unique_id}')->name('pages.')->group(function () {

    // ── Publish Workflow ──────────────────────────────────────────────
    Route::post('/publish',    PublishController::class)->name('publish');
    Route::post('/unpublish',  UnpublishController::class)->name('unpublish');
    Route::post('/archive',    ArchiveController::class)->name('archive');
    Route::post('/save-draft', SaveDraftController::class)->name('save-draft');
    Route::post('/auto-save',  AutoSaveController::class)->name('auto-save');

    // ── Revision History ──────────────────────────────────────────────
    Route::prefix('revisions')->name('revisions.')->group(function () {
        Route::get('/',                                RevisionIndexController::class)->name('index');
        Route::get('/compare',                         RevisionCompareController::class)->name('compare');
        Route::get('/{rev_unique_id}',                 RevisionShowController::class)->name('show');
        Route::post('/{rev_unique_id}/restore',        RevisionRestoreController::class)->name('restore');
        Route::delete('/{rev_unique_id}',              RevisionDeleteController::class)->name('delete');
    });

});

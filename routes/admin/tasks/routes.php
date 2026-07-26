<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Tasks\TaskController;

Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::get('/',                              [TaskController::class, 'index'])->name('index');
    Route::get('/archive',                       [TaskController::class, 'archive'])->name('archive');
    Route::get('/call/{id}',                     [TaskController::class, 'showCall'])->name('call.show');
    Route::get('/create',                        [TaskController::class, 'create'])->name('create');
    Route::post('/',                             [TaskController::class, 'store'])->name('store');

    // Billing Operations sub-workspaces (Fuel/Damage charge resolution +
    // settings). MUST be registered BEFORE the /{task} wildcard below so the
    // literal `billing` segment is not captured as a Task model binding.
    require base_path('routes/admin/tasks/billing/routes.php');

    Route::get('/{task}',                        [TaskController::class, 'show'])->whereNumber('task')->name('show');
    Route::get('/{task}/edit',                   [TaskController::class, 'edit'])->whereNumber('task')->name('edit');
    Route::patch('/{task}',                      [TaskController::class, 'update'])->whereNumber('task')->name('update');
    Route::delete('/{task}',                     [TaskController::class, 'destroy'])->whereNumber('task')->name('destroy');
    Route::post('/{task}/comments',              [TaskController::class, 'storeComment'])->whereNumber('task')->name('comments.store');
    Route::post('/{task}/comments/{comment}/reply', [TaskController::class, 'replyToLinkedComment'])->whereNumber('task')->name('comments.reply');
    Route::post('/{task}/complete',              [TaskController::class, 'completeWithComment'])->whereNumber('task')->name('complete');
    Route::post('/{task}/reassign',              [TaskController::class, 'reassign'])->whereNumber('task')->name('reassign');
    Route::post('/{task}/status',                [TaskController::class, 'updateStatus'])->whereNumber('task')->name('status');
    Route::post('/call/{id}/reassign',           [TaskController::class, 'reassignCall'])->name('call.reassign');
    Route::post('/call/{id}/note',               [TaskController::class, 'storeCallNote'])->name('call.note');
    Route::delete('/call/{id}',                  [TaskController::class, 'destroyCall'])->name('call.destroy');
});

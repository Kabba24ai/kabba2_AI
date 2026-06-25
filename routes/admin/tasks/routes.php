<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Tasks\TaskController;

Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::get('/',                              [TaskController::class, 'index'])->name('index');
    Route::get('/archive',                       [TaskController::class, 'archive'])->name('archive');
    Route::get('/call/{id}',                     [TaskController::class, 'showCall'])->name('call.show');
    Route::get('/create',                        [TaskController::class, 'create'])->name('create');
    Route::post('/',                             [TaskController::class, 'store'])->name('store');
    Route::get('/{task}',                        [TaskController::class, 'show'])->name('show');
    Route::get('/{task}/edit',                   [TaskController::class, 'edit'])->name('edit');
    Route::patch('/{task}',                      [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}',                     [TaskController::class, 'destroy'])->name('destroy');
    Route::post('/{task}/comments',              [TaskController::class, 'storeComment'])->name('comments.store');
    Route::post('/{task}/complete',              [TaskController::class, 'completeWithComment'])->name('complete');
});

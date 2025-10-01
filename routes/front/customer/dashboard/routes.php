<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Customer\Dashboard\IndexController;
use App\Http\Controllers\Front\Customer\Dashboard\DownloadPdfController;
use App\Http\Controllers\Front\Customer\Dashboard\UpdateController;
use App\Http\Controllers\Front\Customer\Dashboard\CheckEmailController;
use App\Http\Controllers\Front\Customer\Dashboard\TaxDocumentUploadController;


Route::prefix('dashboard')->name('dashboard.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

    Route::post('/', UpdateController::class);

    Route::get('/{id}/download', DownloadPdfController::class)->name('download');

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

    Route::post('/taxdoc-upload', TaxDocumentUploadController::class)->name('taxdoc.upload');

    require base_path('routes/front/customer/dashboard/invoice/routes.php');
});

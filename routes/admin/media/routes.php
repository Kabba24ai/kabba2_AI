<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Global\DownloadMediaController;

Route::get('/media/download/{unique_id}', [DownloadMediaController::class, 'download'])
    ->name('download_media');

Route::get('/media/download-signed/{unique_id}', [DownloadMediaController::class, 'signedDownload'])
    ->name('download_sign_media')
    ->middleware('signed');

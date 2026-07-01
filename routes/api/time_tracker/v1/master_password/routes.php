<?php

use App\Http\Controllers\Api\TimeTracker\V1\MasterPassword\VerifyController;
use Illuminate\Support\Facades\Route;

Route::post('/master-password/verify', VerifyController::class);

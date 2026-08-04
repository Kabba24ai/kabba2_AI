<?php

use App\Http\Controllers\Admin\GiftCards\Granted\CreateController as GrantedCreateController;
use App\Http\Controllers\Admin\GiftCards\Granted\StoreController as GrantedStoreController;
use App\Http\Controllers\Admin\GiftCards\IndexController;
use App\Http\Controllers\Admin\GiftCards\Lifecycle\AdjustController;
use App\Http\Controllers\Admin\GiftCards\Lifecycle\LifecycleController;
use App\Http\Controllers\Admin\GiftCards\LookupController;
use App\Http\Controllers\Admin\GiftCards\OverviewController;
use App\Http\Controllers\Admin\GiftCards\Purchased\CreateController as PurchasedCreateController;
use App\Http\Controllers\Admin\GiftCards\Purchased\StoreController as PurchasedStoreController;
use App\Http\Controllers\Admin\GiftCards\ReportingController;
use App\Http\Controllers\Admin\GiftCards\ShowController;
use App\Http\Controllers\Admin\GiftCards\TransactionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gift Cards
|--------------------------------------------------------------------------
|
| The administrative workspace over the stored-value engine.
|
| Cards are addressed by CARD NUMBER rather than id. That number is what an
| operator has in front of them — printed on the card, quoted on the phone —
| so it is what the URL should take. It is unique, non-sequential and not
| guessable, which is exactly why the engine generates it that way.
|
| Purchased and granted creation are separate routes on purpose: selling a
| card and giving one away are different acts with different permissions, and
| a shared route with a mode flag would put them one parameter apart.
|
*/

Route::prefix('gift-cards')
    ->name('gift-cards.')
    ->group(function () {

        Route::get('/', OverviewController::class)->name('overview');
        Route::get('/all', IndexController::class)->name('index');
        Route::get('/transactions', TransactionsController::class)->name('transactions');
        Route::get('/reporting', ReportingController::class)->name('reporting');

        // Point-of-payment validation. Read-only; the authoritative check
        // happens again under a row lock inside redeem().
        Route::post('/lookup', LookupController::class)->name('lookup');

        Route::prefix('purchased')->name('purchased.')->group(function () {
            Route::get('/create', PurchasedCreateController::class)->name('create');
            Route::post('/', PurchasedStoreController::class)->name('store');
        });

        Route::prefix('granted')->name('granted.')->group(function () {
            Route::get('/create', GrantedCreateController::class)->name('create');
            Route::post('/', GrantedStoreController::class)->name('store');
        });

        // {card} is the card_number. Registered last so it cannot swallow
        // the literal segments above.
        Route::prefix('{card}')->group(function () {
            Route::get('/', ShowController::class)->name('show');

            Route::post('/suspend', [LifecycleController::class, 'suspend'])->name('suspend');
            Route::post('/reinstate', [LifecycleController::class, 'reinstate'])->name('reinstate');
            Route::post('/cancel', [LifecycleController::class, 'cancel'])->name('cancel');
            Route::post('/replace', [LifecycleController::class, 'replace'])->name('replace');
            Route::post('/adjust', AdjustController::class)->name('adjust');
        });
    });

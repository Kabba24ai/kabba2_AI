<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SalesReports\V1\SalesReportController;

/*
|--------------------------------------------------------------------------
| Sales Reports API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for sales reports. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Sales trend data endpoints
Route::get('/rolling-30-days', [SalesReportController::class, 'getRolling30Days']);
Route::get('/7-day-comparison', [SalesReportController::class, 'get7DayComparison']);
Route::get('/last-month-comparison', [SalesReportController::class, 'getLastMonthComparison']);

// Top performers endpoints
Route::get('/top-products', [SalesReportController::class, 'getTopProducts']);
Route::get('/top-categories', [SalesReportController::class, 'getTopCategories']);

// Filter options endpoints
Route::get('/categories', [SalesReportController::class, 'getCategories']);
Route::get('/products', [SalesReportController::class, 'getProducts']);
Route::get('/stores', [SalesReportController::class, 'getStores']);

// Pure Sales Report endpoints
Route::get('/sales-summary', [SalesReportController::class, 'getSalesSummary']);
Route::get('/revenue-breakdown', [SalesReportController::class, 'getRevenueBreakdown']);
Route::get('/tax-and-payments', [SalesReportController::class, 'getTaxAndPayments']);
Route::get('/discounts-report', [SalesReportController::class, 'getDiscountsReport']);
Route::get('/refunds-report', [SalesReportController::class, 'getRefundsReport']);
Route::get('/product-sales-details', [SalesReportController::class, 'getProductSalesDetails']);
<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Customers\IndexController;
use App\Http\Controllers\Admin\Crm\Customers\CreateController;
use App\Http\Controllers\Admin\Crm\Customers\EditController;
use App\Http\Controllers\Admin\Crm\Customers\StoreController;
use App\Http\Controllers\Admin\Crm\Customers\UpdateController;
use App\Http\Controllers\Admin\Crm\Customers\ViewUpdateController;
use App\Http\Controllers\Admin\Crm\Customers\DeleteController;
use App\Http\Controllers\Admin\Crm\Customers\CheckEmailController;
use App\Http\Controllers\Admin\Crm\Customers\ViewController;
use App\Http\Controllers\Admin\Crm\Customers\TaxDocumentDeleteController;
use App\Http\Controllers\Admin\Crm\Customers\TaxStatusUpdateController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerStatusUpdateController;
use App\Http\Controllers\Admin\Crm\Customers\TaxDocumentUploadController;
use App\Http\Controllers\Admin\Crm\Customers\BulkDeleteController;
use App\Http\Controllers\Admin\Crm\Customers\Login\ImpersonateController;
use App\Http\Controllers\Admin\Crm\Customers\PasswordResetController;
use App\Http\Controllers\Admin\Crm\Customers\FetchCustomerTags;


//

use App\Http\Controllers\Admin\Crm\Customers\LoginController;

Route::prefix('customers')
->name('customers.')
->group(function ($router) {

    Route::get('/', action: IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class);

    // View
    Route::get('/{unique_id}/view', ViewController::class)->name('view');
    Route::post('/{unique_id}/view', ViewUpdateController::class);

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

    Route::post('/tax-status-update', TaxStatusUpdateController::class)->name('tax_status.update');

    Route::post('/taxdoc-upload', TaxDocumentUploadController::class)->name('taxdoc.upload');

    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');

    // Route::post('/tax-document/{unique_id}', TaxDocumentDeleteController::class)->name('tax-document.delete');
    Route::delete('/tax-document/{unique_id}', TaxDocumentDeleteController::class)
    ->name('tax-document.delete');

    Route::get('/{unique_id}/login', LoginController::class)->name('login');

    // Impersonate Login
    Route::post('/impersonate/{unique_id}', ImpersonateController::class)->name('impersonate-login');

    Route::post('/password/update', PasswordResetController::class)->name('password.update');

    Route::post('/status-update/{unique_id}', CustomerStatusUpdateController::class)->name('status-update.customer');

    // Delete
    Route::post('/bulk-delete', BulkDeleteController::class)->name('bulk-delete');


    Route::get('/{id}/tags/fetch', FetchCustomerTags::class)->name('tags.fetch');




    // customer_account
    require base_path('routes/admin/crm/customers/customer_account/routes.php');


    // invoice
    require base_path('routes/admin/crm/customers/invoice/routes.php');

    // notes
    require base_path('routes/admin/crm/customers/notes/routes.php');

});

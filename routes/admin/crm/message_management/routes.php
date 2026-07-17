<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\Crm\MessageManagement\IndexController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('message-management')
->name('message-management.')
->group(function ($router) {

        Route::get('/', IndexController::class)->name('index');

        require base_path('routes/admin/crm/message_management/sms_broadcast/routes.php');
        require base_path('routes/admin/crm/message_management/sms_funnel/routes.php');


        require base_path('routes/admin/crm/message_management/email_category/routes.php');
        require base_path('routes/admin/crm/message_management/email_template/routes.php');
        require base_path('routes/admin/crm/message_management/sms_category/routes.php');

        require base_path('routes/admin/crm/message_management/sms_created_broadcast/routes.php');

        // Broadcast wizard, saved audiences, and broadcast queue
        require base_path('routes/admin/crm/message_management/broadcast_queue/routes.php');

});

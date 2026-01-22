<?php

use Illuminate\Support\Facades\Route;

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

Route::prefix('website-management')
->name('website-management.')
->group(function ($router) {

// website-management
    // branding
    require base_path('routes/admin/website_management/branding/routes.php');

    // contact_us
    require base_path('routes/admin/website_management/contact_us/routes.php');

    //faq_page
    require base_path('routes/admin/website_management/faq_page/routes.php');


    // footer
     require base_path('routes/admin/website_management/footer/routes.php');

     
    // home_page
     require base_path('routes/admin/website_management/home_page/routes.php');
});

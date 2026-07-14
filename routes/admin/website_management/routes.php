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

        //faq_page
        require base_path('routes/admin/website_management/faq_page/routes.php');

        // footer
        require base_path('routes/admin/website_management/footer/routes.php');

        // home_page
        require base_path('routes/admin/website_management/home_page/routes.php');

        // home page builder
        require base_path('routes/admin/website_management/home_page_builder/routes.php');

        // contact page builder
        require base_path('routes/admin/website_management/contact_page_builder/routes.php');

        // website pages (generic multi-page builder)
        require base_path('routes/admin/website_management/website_pages/routes.php');

        // media library
        require base_path('routes/admin/website_management/media_library/routes.php');

        // navigation builder
        require base_path('routes/admin/website_management/navigation_builder/routes.php');

        // publishing workflow + revision history (scoped to any page by unique_id)
        require base_path('routes/admin/website_management/publishing/routes.php');

        // global theme builder
        require base_path('routes/admin/website_management/theme_builder/routes.php');
    });

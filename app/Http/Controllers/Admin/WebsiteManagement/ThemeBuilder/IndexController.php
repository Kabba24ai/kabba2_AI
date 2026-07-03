<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function __invoke()
    {
        return redirect()->route('admin.website-management.theme-builder.show', 'branding');
    }
}

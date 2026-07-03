<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;

class IndexController extends Controller
{
    public function __invoke()
    {
        $menus = WebsiteMenu::withCount('items')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('admin.website_management.navigation_builder.index', compact('menus'));
    }
}

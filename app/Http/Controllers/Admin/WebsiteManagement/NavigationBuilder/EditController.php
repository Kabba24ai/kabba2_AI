<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Navigation\NavigationService;

class EditController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(string $unique_id)
    {
        $menu = WebsiteMenu::where('unique_id', $unique_id)->firstOrFail();

        $allItems = $menu->items()->with('page')->orderBy('display_order')->get();
        $tree     = $this->navService->buildAdminTree($allItems);

        $pages = WebsitePage::where('status', 'Active')
            ->orderBy('title')
            ->get(['id', 'unique_id', 'title', 'slug']);

        return view('admin.website_management.navigation_builder.edit', compact('menu', 'tree', 'pages'));
    }
}

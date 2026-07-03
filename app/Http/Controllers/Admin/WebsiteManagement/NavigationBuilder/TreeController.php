<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use App\Services\Navigation\NavigationService;

class TreeController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(string $unique_id)
    {
        $menu     = WebsiteMenu::where('unique_id', $unique_id)->firstOrFail();
        $allItems = $menu->items()->with('page')->orderBy('display_order')->get();
        $tree     = $this->navService->buildAdminTree($allItems);

        return view('admin.website_management.navigation_builder.partials._tree', [
            'items' => $tree,
            'depth' => 0,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use App\Services\Navigation\NavigationService;

class DestroyController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(string $unique_id)
    {
        $menu = WebsiteMenu::where('unique_id', $unique_id)->firstOrFail();
        $this->navService->clearCache($menu->menu_key);
        $menu->delete();

        return redirect()
            ->route('admin.website-management.navigation-builder.index')
            ->with('success', 'Menu "' . $menu->name . '" deleted.');
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use App\Services\Navigation\NavigationService;

class DeleteController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(string $unique_id)
    {
        $item    = WebsiteMenuItem::where('unique_id', $unique_id)->firstOrFail();
        $menuKey = $item->menu->menu_key;

        // Promote children to the deleted item's parent level
        WebsiteMenuItem::where('parent_id', $item->id)
            ->update(['parent_id' => $item->parent_id]);

        $item->delete();
        $this->navService->clearCache($menuKey);

        return response()->json(['success' => true]);
    }
}

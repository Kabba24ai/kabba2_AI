<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use App\Services\Navigation\NavigationService;
use Illuminate\Http\Request;

class SortController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'menu_unique_id' => 'required|string|exists:website_menus,unique_id',
            'tree'           => 'required|array',
        ]);

        $menu = WebsiteMenu::where('unique_id', $validated['menu_unique_id'])->firstOrFail();

        $this->processTree($validated['tree'], null, $menu->id);
        $this->navService->clearCache($menu->menu_key);

        return response()->json(['success' => true]);
    }

    private function processTree(array $nodes, ?int $parentId, int $menuId): void
    {
        foreach ($nodes as $index => $node) {
            $item = WebsiteMenuItem::where('unique_id', $node['id'])
                ->where('website_menu_id', $menuId)
                ->first();
            if (!$item) continue;

            $item->update(['parent_id' => $parentId, 'display_order' => $index]);

            if (!empty($node['children'])) {
                $this->processTree($node['children'], $item->id, $menuId);
            }
        }
    }
}

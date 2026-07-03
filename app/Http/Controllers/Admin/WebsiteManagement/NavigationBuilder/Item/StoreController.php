<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use App\Services\Navigation\NavigationService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'menu_unique_id'   => 'required|string|exists:website_menus,unique_id',
            'parent_unique_id' => 'nullable|string|exists:website_menu_items,unique_id',
            'title'            => 'required|string|max:200',
            'type'             => 'required|in:internal_page,external_url,anchor,email,phone',
            'page_id'          => 'nullable|integer|exists:website_pages,id',
            'url'              => 'nullable|string|max:500',
            'icon'             => 'nullable|string|max:150',
            'icon_media_id'    => 'nullable|integer|exists:media,id',
            'css_class'        => 'nullable|string|max:200',
            'target'           => 'nullable|in:_self,_blank',
            'rel'              => 'nullable|string|max:100',
            'visibility'       => 'nullable|in:both,desktop_only,mobile_only',
            'status'           => 'nullable|in:Active,Inactive',
        ]);

        $menu = WebsiteMenu::where('unique_id', $validated['menu_unique_id'])->firstOrFail();

        $parentId = null;
        if (!empty($validated['parent_unique_id'])) {
            $parent   = WebsiteMenuItem::where('unique_id', $validated['parent_unique_id'])->firstOrFail();
            $parentId = $parent->id;
        }

        $maxOrder = WebsiteMenuItem::where('website_menu_id', $menu->id)
            ->where('parent_id', $parentId)
            ->max('display_order') ?? -1;

        $item = WebsiteMenuItem::create([
            'website_menu_id' => $menu->id,
            'parent_id'       => $parentId,
            'title'           => $validated['title'],
            'type'            => $validated['type'],
            'page_id'         => $validated['page_id'] ?? null,
            'url'             => $validated['url'] ?? null,
            'icon'            => $validated['icon'] ?? null,
            'icon_media_id'   => $validated['icon_media_id'] ?? null,
            'css_class'       => $validated['css_class'] ?? null,
            'target'          => $validated['target'] ?? '_self',
            'rel'             => $validated['rel'] ?? null,
            'visibility'      => $validated['visibility'] ?? 'both',
            'status'          => $validated['status'] ?? 'Active',
            'display_order'   => $maxOrder + 1,
        ]);

        $this->navService->clearCache($menu->menu_key);

        return response()->json(['success' => true, 'item' => $item->load('page')->toBuilderArray()]);
    }
}

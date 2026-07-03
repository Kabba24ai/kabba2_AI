<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use App\Services\Navigation\NavigationService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $menu = WebsiteMenu::where('unique_id', $unique_id)->firstOrFail();

        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'menu_key'      => ['required', 'string', 'max:100', 'unique:website_menus,menu_key,' . $menu->id, 'regex:/^[a-z0-9_]+$/'],
            'description'   => 'nullable|string|max:500',
            'status'        => 'required|in:Active,Inactive',
            'display_order' => 'nullable|integer|min:0',
        ], [
            'menu_key.regex' => 'Menu key must be lowercase letters, numbers and underscores only.',
        ]);

        $oldKey = $menu->menu_key;
        $menu->update($validated);

        $this->navService->clearCache($oldKey);
        $this->navService->clearCache($menu->menu_key);

        return response()->json(['success' => true, 'menu' => [
            'name'     => $menu->name,
            'menu_key' => $menu->menu_key,
            'status'   => $menu->status,
        ]]);
    }
}

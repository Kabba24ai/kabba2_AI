<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenu;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'menu_key'      => ['required', 'string', 'max:100', 'unique:website_menus,menu_key', 'regex:/^[a-z0-9_]+$/'],
            'description'   => 'nullable|string|max:500',
            'status'        => 'required|in:Active,Inactive',
            'display_order' => 'nullable|integer|min:0',
        ], [
            'menu_key.regex' => 'Menu key must be lowercase letters, numbers and underscores only.',
        ]);

        $menu = WebsiteMenu::create($validated);

        return redirect()
            ->route('admin.website-management.navigation-builder.edit', $menu->unique_id)
            ->with('success', 'Menu "' . $menu->name . '" created. Start adding items below.');
    }
}

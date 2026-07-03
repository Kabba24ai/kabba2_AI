<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use App\Services\Navigation\NavigationService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $item = WebsiteMenuItem::where('unique_id', $unique_id)->firstOrFail();

        $validated = $request->validate([
            'title'         => 'required|string|max:200',
            'type'          => 'required|in:internal_page,external_url,anchor,email,phone',
            'page_id'       => 'nullable|integer|exists:website_pages,id',
            'url'           => 'nullable|string|max:500',
            'icon'          => 'nullable|string|max:150',
            'icon_media_id' => 'nullable|integer|exists:media,id',
            'css_class'     => 'nullable|string|max:200',
            'target'        => 'nullable|in:_self,_blank',
            'rel'           => 'nullable|string|max:100',
            'visibility'    => 'nullable|in:both,desktop_only,mobile_only',
            'status'        => 'nullable|in:Active,Inactive',
        ]);

        $item->update($validated);
        $this->navService->clearCache($item->menu->menu_key);

        return response()->json(['success' => true, 'item' => $item->fresh()->load('page')->toBuilderArray()]);
    }
}

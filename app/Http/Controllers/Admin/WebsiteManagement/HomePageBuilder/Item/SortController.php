<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use Illuminate\Http\Request;
use App\Services\Website\HomePageService;

class SortController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'items'          => 'required|array',
            'items.*.id'     => 'required|string|exists:website_section_items,unique_id',
            'items.*.order'  => 'required|integer|min:0',
        ]);

        foreach ($request->items as $entry) {
            WebsiteSectionItem::where('unique_id', $entry['id'])
                ->update(['display_order' => $entry['order']]);
        }

        HomePageService::clearCache();
        return response()->json(['success' => true]);
    }
}

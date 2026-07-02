<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Section;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\HomePageService;
use Illuminate\Http\Request;

class SortController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'sections'           => 'required|array',
            'sections.*.id'      => 'required|string|exists:website_page_sections,unique_id',
            'sections.*.order'   => 'required|integer|min:0',
        ]);

        foreach ($request->sections as $entry) {
            WebsitePageSection::where('unique_id', $entry['id'])
                ->update(['display_order' => $entry['order']]);
        }

        HomePageService::clearCache();
        return response()->json(['success' => true]);
    }
}

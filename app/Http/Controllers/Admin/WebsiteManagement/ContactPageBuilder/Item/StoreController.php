<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Http\Requests\Admin\WebsiteManagement\ContactPageBuilder\Item\StoreItemRequest;
use App\Services\Website\ContactPageService;

class StoreController extends Controller
{
    public function __invoke(StoreItemRequest $request)
    {
        $validated = $request->validated();
        $section   = WebsitePageSection::where('unique_id', $validated['section_unique_id'])->firstOrFail();

        $validated['website_page_section_id'] = $section->id;
        $validated['display_order']            = $validated['display_order']
            ?? ($section->items()->max('display_order') + 1);

        unset($validated['section_unique_id']);

        WebsiteSectionItem::create($validated);

        ContactPageService::clearCache();
        session()->flash('success', 'Store added successfully.');
        return redirect()->route('admin.website-management.contact-builder.index', ['tab' => $section->section_key]);
    }
}

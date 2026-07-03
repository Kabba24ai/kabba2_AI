<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item\StoreItemRequest;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\WebsiteSectionItemService;

class StoreController extends Controller
{
    public function __construct(private WebsiteSectionItemService $itemService) {}

    public function __invoke(StoreItemRequest $request)
    {
        $validated = $request->validated();
        $section   = WebsitePageSection::where('unique_id', $validated['section_unique_id'])
            ->with('page')
            ->firstOrFail();

        $this->itemService->store($section, $validated, $request);

        $pageUniqueId = $section->page->unique_id;
        session()->flash('success', 'Item added successfully.');

        return redirect()->route('admin.website-management.pages.edit', $pageUniqueId)
            ->with(['tab' => $section->section_key]);
    }
}

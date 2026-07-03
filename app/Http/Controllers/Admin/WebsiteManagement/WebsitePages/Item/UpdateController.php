<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item\UpdateItemRequest;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Services\Website\WebsiteSectionItemService;

class UpdateController extends Controller
{
    public function __construct(private WebsiteSectionItemService $itemService) {}

    public function __invoke(UpdateItemRequest $request, string $unique_id)
    {
        $item = WebsiteSectionItem::where('unique_id', $unique_id)
            ->with('section.page')
            ->firstOrFail();

        $this->itemService->update($item, $request->validated(), $request);

        $pageUniqueId = $item->section->page->unique_id;
        $tab          = $item->section->section_key;
        session()->flash('success', 'Item updated successfully.');

        return redirect()->route('admin.website-management.pages.edit', [$pageUniqueId, 'tab' => $tab]);
    }
}

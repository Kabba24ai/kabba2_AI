<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Services\Website\WebsiteSectionItemService;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function __construct(private WebsiteSectionItemService $itemService) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $item = WebsiteSectionItem::where('unique_id', $unique_id)
            ->with('section.page')
            ->firstOrFail();

        $pageUniqueId = $item->section->page->unique_id;
        $tab          = $item->section->section_key;

        $this->itemService->delete($item);

        session()->flash('success', 'Item deleted successfully.');

        return redirect()->route('admin.website-management.pages.edit', [$pageUniqueId, 'tab' => $tab]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item\StoreItemRequest;
use App\Helpers\MediaHelper;
use App\Services\Website\HomePageService;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Concerns\RedirectsToSectionEditor;

class StoreController extends Controller
{
    use RedirectsToSectionEditor;

    public function __invoke(StoreItemRequest $request)
    {
        $validated = $request->validated();
        $section   = WebsitePageSection::where('unique_id', $validated['section_unique_id'])->firstOrFail();

        // Handle item image
        if ($request->hasFile('image')) {
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('image'), 'website_builder', null);
            if (!empty($mediaData['mediaObj'])) {
                $validated['image'] = $mediaData['mediaObj']->id;
            }
        }

        // Auto-increment display order
        $validated['website_page_section_id'] = $section->id;
        $validated['display_order'] = $validated['display_order']
            ?? ($section->items()->max('display_order') + 1);

        unset($validated['section_unique_id']);

        WebsiteSectionItem::create($validated);

        HomePageService::clearCache();
        session()->flash('success', 'Item added successfully.');
        return $this->redirectToSectionEditor($section->section_key);
    }
}

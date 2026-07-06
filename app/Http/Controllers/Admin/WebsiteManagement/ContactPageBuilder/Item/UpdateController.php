<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Http\Requests\Admin\WebsiteManagement\ContactPageBuilder\Item\UpdateItemRequest;
use App\Services\Website\ContactPageService;

class UpdateController extends Controller
{
    public function __invoke(UpdateItemRequest $request, string $unique_id)
    {
        $item      = WebsiteSectionItem::where('unique_id', $unique_id)->firstOrFail();
        $validated = $request->validated();

        if (isset($validated['content']) && is_array($validated['content'])) {
            $validated['content'] = array_merge($item->content ?? [], $validated['content']);
        }

        unset($validated['image']);
        $item->update($validated);

        ContactPageService::clearCache();
        $tab = $item->section?->section_key ?? 'locations';
        session()->flash('success', 'Store updated successfully.');
        return redirect()->route('admin.website-management.contact-builder.index', ['tab' => $tab]);
    }
}

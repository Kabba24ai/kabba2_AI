<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\Item\UpdateItemRequest;
use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use App\Services\Website\HomePageService;

class UpdateController extends Controller
{
    public function __invoke(UpdateItemRequest $request, string $unique_id)
    {
        $item      = WebsiteSectionItem::where('unique_id', $unique_id)->firstOrFail();
        $validated = $request->validated();

        // Handle item image
        if ($request->hasFile('image')) {
            if ($item->image) {
                $old = Media::find($item->image);
                if ($old) MediaHelper::removeFile($old);
            }
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('image'), 'website_builder', null);
            if (!empty($mediaData['mediaObj'])) {
                $validated['image'] = $mediaData['mediaObj']->id;
            }
        } else {
            unset($validated['image']);
        }

        // Merge JSON content
        if (isset($validated['content']) && is_array($validated['content'])) {
            $existing = $item->content ?? [];
            $validated['content'] = array_merge($existing, $validated['content']);
        }

        $item->update($validated);

        $tab = $item->section?->section_key ?? 'hero';
        HomePageService::clearCache();
        session()->flash('success', 'Item updated successfully.');
        return redirect()->route('admin.website-management.home-builder.index', ['tab' => $tab]);
    }
}

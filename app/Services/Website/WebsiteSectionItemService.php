<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsitePageSection;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use Illuminate\Http\Request;

class WebsiteSectionItemService
{
    public function store(WebsitePageSection $section, array $validated, Request $request): WebsiteSectionItem
    {
        if ($request->hasFile('image')) {
            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset', $request->file('image'), 'website_builder', null
            );
            if (!empty($mediaData['mediaObj'])) {
                $validated['image'] = $mediaData['mediaObj']->id;
            }
        } elseif ($request->filled('media_id') && is_numeric($request->input('media_id'))) {
            $validated['image'] = (int) $request->input('media_id');
        }

        $validated['website_page_section_id'] = $section->id;
        $validated['display_order'] = $validated['display_order']
            ?? ($section->items()->max('display_order') + 1);

        unset($validated['section_unique_id']);

        return WebsiteSectionItem::create($validated);
    }

    public function update(WebsiteSectionItem $item, array $validated, Request $request): void
    {
        if ($request->hasFile('image')) {
            if ($item->image) {
                $old = Media::find($item->image);
                if ($old) MediaHelper::removeFile($old);
            }
            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset', $request->file('image'), 'website_builder', null
            );
            if (!empty($mediaData['mediaObj'])) {
                $validated['image'] = $mediaData['mediaObj']->id;
            }
        } elseif ($request->filled('media_id') && is_numeric($request->input('media_id'))) {
            $validated['image'] = (int) $request->input('media_id');
        } else {
            unset($validated['image']);
        }

        if (isset($validated['content']) && is_array($validated['content'])) {
            $validated['content'] = array_merge($item->content ?? [], $validated['content']);
        }

        $item->update($validated);
    }

    public function delete(WebsiteSectionItem $item): void
    {
        if ($item->image) {
            $media = Media::find($item->image);
            if ($media) MediaHelper::removeFile($media);
        }
        $item->delete();
    }

    public function duplicate(WebsiteSectionItem $item): WebsiteSectionItem
    {
        $copy                = $item->replicate(['unique_id', 'image']);
        $copy->title         = ($item->title ?? 'Item') . ' (Copy)';
        $copy->display_order = $item->display_order + 1;
        $copy->image         = null;
        $copy->save();
        return $copy;
    }

    public function sort(array $items): void
    {
        foreach ($items as $row) {
            WebsiteSectionItem::where('unique_id', $row['id'])
                ->update(['display_order' => $row['order']]);
        }
    }

    /**
     * Bulk-create default items for a newly seeded section.
     * Each entry in $items is a plain array of WebsiteSectionItem column values.
     */
    public function createDefaults(WebsitePageSection $section, array $items): void
    {
        foreach ($items as $data) {
            WebsiteSectionItem::create(array_merge($data, [
                'website_page_section_id' => $section->id,
                'status'                  => $data['status'] ?? 'Active',
            ]));
        }
    }
}

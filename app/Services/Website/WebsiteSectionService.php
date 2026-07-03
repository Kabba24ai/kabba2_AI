<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsitePageSection;
use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use Illuminate\Http\Request;

class WebsiteSectionService
{
    public function update(WebsitePageSection $section, array $validated, Request $request): void
    {
        if ($request->hasFile('image')) {
            if ($section->image) {
                $old = Media::find($section->image);
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
            $validated['content'] = array_merge($section->content ?? [], $validated['content']);
        }

        $section->update($validated);
    }

    public function sort(array $sections): void
    {
        foreach ($sections as $row) {
            WebsitePageSection::where('unique_id', $row['id'])
                ->update(['display_order' => $row['order']]);
        }
    }

    public function removeImage(WebsitePageSection $section): void
    {
        if ($section->image) {
            $media = Media::find($section->image);
            if ($media) MediaHelper::removeFile($media);
            $section->update(['image' => null]);
        }
    }
}

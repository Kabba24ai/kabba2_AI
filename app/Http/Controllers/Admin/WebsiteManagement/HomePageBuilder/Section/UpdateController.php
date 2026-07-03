<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Section;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\UpdateSectionRequest;
use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use App\Services\Website\HomePageService;

class UpdateController extends Controller
{
    public function __invoke(UpdateSectionRequest $request, string $unique_id)
    {
        $section   = WebsitePageSection::where('unique_id', $unique_id)->firstOrFail();
        $validated = $request->validated();

        // Handle section image — file upload takes precedence over media picker
        if ($request->hasFile('image')) {
            if ($section->image) {
                $old = Media::find($section->image);
                if ($old) MediaHelper::removeFile($old);
            }
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('image'), 'website_builder', null);
            if (!empty($mediaData['mediaObj'])) {
                $validated['image'] = $mediaData['mediaObj']->id;
            }
        } elseif ($request->filled('media_id')) {
            // Image chosen from media library
            $validated['image'] = $validated['media_id'];
        } else {
            unset($validated['image']);
        }
        unset($validated['media_id']);

        // Merge JSON content fields
        if (isset($validated['content']) && is_array($validated['content'])) {
            $existing = $section->content ?? [];
            $validated['content'] = array_merge($existing, $validated['content']);
        }

        $section->update($validated);

        HomePageService::clearCache();
        session()->flash('success', ucwords(str_replace('_', ' ', $section->section_key)) . ' section updated successfully.');
        return redirect()->route('admin.website-management.home-builder.index', ['tab' => $section->section_key]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\UpdatePageRequest;
use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use App\Services\Website\ContactPageService;

class UpdateController extends Controller
{
    public function __invoke(UpdatePageRequest $request)
    {
        $validated = $request->validated();

        $page = WebsitePage::firstOrCreate(
            ['page_key' => 'contact'],
            ['title' => 'Contact Us', 'slug' => 'contact-us', 'status' => 'Active']
        );

        if ($request->hasFile('og_image')) {
            if ($page->og_image) {
                $old = Media::find($page->og_image);
                if ($old) MediaHelper::removeFile($old);
            }
            $mediaData = MediaHelper::uploadStorageFile('Public Asset', $request->file('og_image'), 'website_builder', null);
            if (!empty($mediaData['mediaObj'])) {
                $validated['og_image'] = $mediaData['mediaObj']->id;
            }
        } else {
            unset($validated['og_image']);
        }

        $page->update($validated);

        ContactPageService::clearCache();
        session()->flash('success', 'SEO settings updated successfully.');
        return redirect()->route('admin.website-management.contact-builder.index', ['tab' => 'seo']);
    }
}

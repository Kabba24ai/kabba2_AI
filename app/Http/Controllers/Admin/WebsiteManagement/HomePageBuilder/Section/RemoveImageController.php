<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Section;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\HomePageService;
use App\Traits\HandlesMediaUpload;
use Illuminate\Http\Request;

class RemoveImageController extends Controller
{
    use HandlesMediaUpload;

    public function __invoke(Request $request, string $unique_id)
    {
        $section = WebsitePageSection::where('unique_id', $unique_id)->firstOrFail();
        $this->removeImage($section, 'image');

        HomePageService::clearCache();

        session()->flash('success', 'Image removed.');
        return redirect()->route('admin.website-management.home-builder.index', [
            'tab' => $section->section_key,
        ]);
    }
}

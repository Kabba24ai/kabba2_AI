<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Section;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\WebsiteSectionService;
use Illuminate\Http\Request;

class RemoveImageController extends Controller
{
    public function __construct(private WebsiteSectionService $sectionService) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $section = WebsitePageSection::where('unique_id', $unique_id)
            ->with('page')
            ->firstOrFail();

        $this->sectionService->removeImage($section);

        $pageUniqueId = $section->page->unique_id;
        session()->flash('success', 'Section image removed.');

        return redirect()->route('admin.website-management.pages.edit', $pageUniqueId)
            ->with(['tab' => $section->section_key]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Section;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\HomePageBuilder\UpdateSectionRequest;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\WebsiteSectionService;

class UpdateController extends Controller
{
    public function __construct(private WebsiteSectionService $sectionService) {}

    public function __invoke(UpdateSectionRequest $request, string $unique_id)
    {
        $section = WebsitePageSection::where('unique_id', $unique_id)
            ->with('page')
            ->firstOrFail();

        $this->sectionService->update($section, $request->validated(), $request);

        $pageUniqueId = $section->page->unique_id;
        session()->flash('success', ucwords(str_replace('_', ' ', $section->section_key)) . ' section updated.');

        return redirect()->route('admin.website-management.pages.edit', $pageUniqueId)
            ->with(['tab' => $section->section_key]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\Section;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Http\Requests\Admin\WebsiteManagement\ContactPageBuilder\UpdateSectionRequest;
use App\Services\Website\WebsiteSectionService;
use App\Services\Website\ContactPageService;

class UpdateController extends Controller
{
    public function __construct(private WebsiteSectionService $sectionService) {}

    public function __invoke(UpdateSectionRequest $request, string $unique_id)
    {
        $section   = WebsitePageSection::where('unique_id', $unique_id)->firstOrFail();
        $validated = $request->validated();

        $this->sectionService->update($section, $validated, $request);

        ContactPageService::clearCache();
        $label = ucwords(str_replace('_', ' ', $section->section_key));
        session()->flash('success', "{$label} section updated successfully.");
        return redirect()->route('admin.website-management.contact-builder.index', ['tab' => $section->section_key]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Section;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\ComponentRegistry;
use App\Services\Website\WebsiteSectionItemService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(
        private ComponentRegistry         $registry,
        private WebsiteSectionItemService $itemService
    ) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            'page_unique_id' => 'required|string',
            'component_key'  => 'required|string',
        ]);

        $component = $this->registry->find($request->component_key);

        if (!$component || !$component->showInPicker()) {
            return back()->withErrors(['component_key' => 'Invalid or unavailable component.']);
        }

        $page = WebsitePage::where('unique_id', $request->page_unique_id)->firstOrFail();

        // Generate a unique section_key for this page (component_key, then component_key_2, etc.)
        $baseKey    = $component->key();
        $sectionKey = $baseKey;
        $existing   = $page->sections()->pluck('section_key')->toArray();
        $suffix     = 2;
        while (in_array($sectionKey, $existing)) {
            $sectionKey = $baseKey . '_' . $suffix++;
        }

        $defaults        = $component->defaultData();
        $sectionDefaults = $defaults['section'] ?? [];
        $itemDefaults    = $defaults['items']   ?? [];

        $section = WebsitePageSection::create([
            'website_page_id' => $page->id,
            'section_key'     => $sectionKey,
            'section_type'    => $component->key(),
            'section_name'    => $component->displayName(),
            'title'           => $sectionDefaults['title']    ?? null,
            'subtitle'        => $sectionDefaults['subtitle'] ?? null,
            'content'         => !empty($sectionDefaults['content']) ? $sectionDefaults['content'] : null,
            'display_order'   => ($page->sections()->max('display_order') ?? 0) + 1,
            'status'          => 'Active',
        ]);

        if (!empty($itemDefaults)) {
            $this->itemService->createDefaults($section, $itemDefaults);
        }

        return redirect()
            ->to(route('admin.website-management.pages.edit', $page->unique_id) . '?tab=' . $sectionKey)
            ->with('success', $component->displayName() . ' section added successfully.');
    }
}

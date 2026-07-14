<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Footer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Configurations\Setting;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\ComponentRegistry;

class IndexController extends Controller
{
    public function __construct(private ComponentRegistry $registry) {}

    public function __invoke(Request $request)
    {
        $settings = Setting::whereNotIn('setting_type', ['Email Settings'])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->groupBy('setting_type'); // keys are strings

        session()->forget('master_verified');
        $settings = $settings->sortKeys();
        $settings = $settings->map(function ($group) {
            return $group->keyBy('setting_name');
        });

        // Global Footer + Feature Strip editors.
        // The data lives on the `home` website page (section_keys `footer` and
        // `feature_strip`) but is rendered globally by the front layout on every
        // public page — this screen is the single place to edit it.
        $page       = WebsitePage::where('page_key', 'home')->first();
        $sections   = collect();
        $itemsByKey = collect();

        if ($page) {
            $page->load(['sections.items']);
            $sections   = $page->sections->keyBy('section_key');
            $itemsByKey = $sections->mapWithKeys(fn ($s) => [$s->section_key => $s->items]);
        }

        $globalKeys = ['footer', 'feature_strip'];
        $components = collect($globalKeys)
            ->filter(fn ($key) => $this->registry->has($key))
            ->mapWithKeys(fn ($key) => [$key => $this->registry->find($key)]);

        return view('admin.website_management.footer.index', [
            'settings'   => $settings,
            'page'       => $page,
            'sections'   => $sections,
            'itemsByKey' => $itemsByKey,
            'components' => $components,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\Stores\Store;
use App\Services\Website\ComponentRegistry;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(private ComponentRegistry $registry) {}

    public function __invoke(Request $request)
    {
        $page = WebsitePage::where('page_key', 'home')->first();

        $sections   = collect();
        $itemsByKey = collect();

        if ($page) {
            $page->load(['sections.items']);
            $sections           = $page->sections->keyBy('section_key');
            $itemsByKey         = $sections->mapWithKeys(fn ($s) => [$s->section_key => $s->items]);
            $allSectionsOrdered = $page->sections->sortBy('display_order')->values();
        }

        $stores = Store::active()->orderByAdmin()->get(['id', 'unique_id', 'store_name']);

        // Feature Strip + Footer are global site components — edited on
        // Website Management → Footer. Branding (logo/favicon) is edited on
        // Website Management → Branding, not here.
        $allowedKeys = ['hero', 'contact_strip', 'featured_rentals', 'seo'];
        $components  = collect($allowedKeys)
            ->filter(fn ($key) => $this->registry->has($key))
            ->mapWithKeys(fn ($key) => [$key => $this->registry->find($key)]);

        return view('admin.website_management.home_page_builder.index', [
            'page'                => $page,
            'sections'            => $sections,
            'itemsByKey'          => $itemsByKey,
            'allSectionsOrdered'  => $allSectionsOrdered ?? collect(),
            'stores'              => $stores,
            'registry'            => $this->registry,
            'components'          => $components,
        ]);
    }
}

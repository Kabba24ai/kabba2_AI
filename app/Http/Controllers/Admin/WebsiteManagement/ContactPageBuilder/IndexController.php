<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\ComponentRegistry;
use App\Services\Website\WebsitePageBuilderService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(
        private ComponentRegistry $registry,
        private WebsitePageBuilderService $builder,
    ) {}

    public function __invoke(Request $request)
    {
        $page    = WebsitePage::where('page_key', 'contact')->firstOrFail();
        $context = $this->builder->getBuilderContext($page);

        // Ordered to match the contact page's visual flow, not the registry registration order.
        // No `hero`: the contact page has no header band — it opens with the
        // shared contact strip (historical hero rows stay in the DB, unrendered).
        $allowedKeys = ['contact_strip', 'locations', 'question_cta', 'seo'];
        $components  = collect($allowedKeys)
            ->filter(fn ($key) => $this->registry->has($key))
            ->mapWithKeys(fn ($key) => [$key => $this->registry->find($key)]);

        return view('admin.website_management.contact_page_builder.index', array_merge($context, [
            'registry'    => $this->registry,
            'components'  => $components,
            'activeTab'   => $request->get('tab', 'contact_strip'),
            'routePrefix' => 'admin.website-management.contact-builder',
        ]));
    }
}

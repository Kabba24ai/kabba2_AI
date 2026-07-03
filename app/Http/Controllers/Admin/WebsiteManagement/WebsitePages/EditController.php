<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\WebsitePageBuilderService;
use App\Services\Website\ComponentRegistry;
use Illuminate\Http\Request;

class EditController extends Controller
{
    public function __construct(
        private WebsitePageBuilderService $builderService,
        private ComponentRegistry         $registry
    ) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $page = WebsitePage::where('unique_id', $unique_id)->firstOrFail();

        $context = $this->builderService->getBuilderContext($page);

        return view('admin.website_management.website_pages.edit', array_merge($context, [
            'registry'         => $this->registry,
            'builderIndexUrl'  => route('admin.website-management.pages.edit', $page->unique_id),
            'routePrefix'      => 'admin.website-management.pages',
        ]));
    }
}

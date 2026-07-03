<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\PageRevisionService;

class IndexController extends Controller
{
    public function __construct(private PageRevisionService $revisionService) {}

    public function __invoke(string $page_unique_id)
    {
        $page      = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();
        $revisions = $this->revisionService->getRevisions($page, 30);

        return view('admin.website_management.pages.revisions.index', compact('page', 'revisions'));
    }
}

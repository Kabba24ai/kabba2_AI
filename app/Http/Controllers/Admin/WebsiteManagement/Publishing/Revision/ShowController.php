<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageRevision;

class ShowController extends Controller
{
    public function __invoke(string $page_unique_id, string $rev_unique_id)
    {
        $page     = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();
        $revision = WebsitePageRevision::withTrashed()
                        ->where('unique_id', $rev_unique_id)
                        ->where('website_page_id', $page->id)
                        ->with('author')
                        ->firstOrFail();

        return view('admin.website_management.pages.revisions.show', compact('page', 'revision'));
    }
}

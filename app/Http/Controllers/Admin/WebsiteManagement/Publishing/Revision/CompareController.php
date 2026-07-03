<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageRevision;
use App\Services\Website\RevisionCompareService;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function __construct(private RevisionCompareService $compareService) {}

    public function __invoke(Request $request, string $page_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();

        $validated = $request->validate([
            'a' => 'required|string',   // base revision unique_id
            'b' => 'required|string',   // compare revision unique_id
        ]);

        $base    = WebsitePageRevision::withTrashed()
                       ->where('unique_id', $validated['a'])
                       ->where('website_page_id', $page->id)
                       ->with('author')
                       ->firstOrFail();

        $compare = WebsitePageRevision::withTrashed()
                       ->where('unique_id', $validated['b'])
                       ->where('website_page_id', $page->id)
                       ->with('author')
                       ->firstOrFail();

        // Ensure base is always the older one
        if ($base->revision_number > $compare->revision_number) {
            [$base, $compare] = [$compare, $base];
        }

        $diff       = $this->compareService->compare($base, $compare);
        $revisions  = $page->revisions()->withTrashed()->get(['id', 'unique_id', 'revision_number', 'created_at', 'change_summary']);

        return view('admin.website_management.pages.revisions.compare',
            compact('page', 'base', 'compare', 'diff', 'revisions'));
    }
}

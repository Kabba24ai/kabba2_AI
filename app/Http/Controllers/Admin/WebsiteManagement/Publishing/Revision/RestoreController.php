<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageRevision;
use App\Models\WebsiteManagement\WebsiteRevisionLog;
use App\Services\Website\PagePublishService;
use App\Services\Website\PageRevisionService;
use Illuminate\Http\Request;

class RestoreController extends Controller
{
    public function __construct(
        private PageRevisionService $revisionService,
        private PagePublishService $publishService,
    ) {}

    public function __invoke(Request $request, string $page_unique_id, string $rev_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();

        $revision = WebsitePageRevision::withTrashed()
                        ->where('unique_id', $rev_unique_id)
                        ->where('website_page_id', $page->id)
                        ->firstOrFail();

        $newRevision = $this->revisionService->restoreRevision($revision);

        // Restore rewrites the live section rows — the public site must not
        // keep serving the pre-restore content from cache.
        $this->publishService->clearPublishedCache($page);

        WebsiteRevisionLog::create([
            'website_page_id'          => $page->id,
            'website_page_revision_id' => $newRevision->id,
            'user_id'                  => auth()->id(),
            'action'                   => 'restored',
            'description'              => "Restored from revision #{$revision->revision_number}",
            'ip_address'               => $request->ip(),
            'user_agent'               => $request->userAgent(),
            'metadata'                 => ['from_revision_id' => $revision->id, 'from_revision_number' => $revision->revision_number],
        ]);

        return response()->json([
            'success'         => true,
            'message'         => "Page restored from revision #{$revision->revision_number}.",
            'new_revision_number' => $newRevision->revision_number,
            'redirect'        => route('admin.website-management.pages.revisions.index', $page->unique_id),
        ]);
    }
}

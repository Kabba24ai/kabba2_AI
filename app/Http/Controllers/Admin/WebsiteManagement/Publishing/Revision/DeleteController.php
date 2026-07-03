<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Revision;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageRevision;
use App\Models\WebsiteManagement\WebsiteRevisionLog;
use App\Services\Website\PageRevisionService;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function __construct(private PageRevisionService $revisionService) {}

    public function __invoke(Request $request, string $page_unique_id, string $rev_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();

        $revision = WebsitePageRevision::where('unique_id', $rev_unique_id)
                        ->where('website_page_id', $page->id)
                        ->firstOrFail();

        try {
            $revNumber = $revision->revision_number;
            $this->revisionService->deleteRevision($revision);

            WebsiteRevisionLog::create([
                'website_page_id' => $page->id,
                'user_id'         => auth()->id(),
                'action'          => 'revision_deleted',
                'description'     => "Revision #{$revNumber} deleted.",
                'ip_address'      => $request->ip(),
            ]);

            return response()->json(['success' => true, 'message' => "Revision #{$revNumber} deleted."]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

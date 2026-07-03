<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\PagePublishService;
use Illuminate\Http\Request;

class SaveDraftController extends Controller
{
    public function __construct(private PagePublishService $publishService) {}

    public function __invoke(Request $request, string $page_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();

        $validated = $request->validate([
            'change_summary' => 'nullable|string|max:300',
        ]);

        $revision = $this->publishService->saveDraft($page, $validated['change_summary'] ?? null);

        return response()->json([
            'success'          => true,
            'message'          => 'Draft saved.',
            'revision_number'  => $revision->revision_number,
            'revision_unique_id' => $revision->unique_id,
            'saved_at'         => $revision->created_at->diffForHumans(),
        ]);
    }
}

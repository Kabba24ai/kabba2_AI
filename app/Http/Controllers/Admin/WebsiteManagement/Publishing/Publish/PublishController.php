<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\PagePublishService;

class PublishController extends Controller
{
    public function __construct(private PagePublishService $publishService) {}

    public function __invoke(string $page_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();
        $revision = $this->publishService->publish($page);

        return response()->json([
            'success'      => true,
            'message'      => 'Page published successfully.',
            'publish_status' => $page->publish_status,
            'published_at' => $page->published_at?->diffForHumans(),
            'revision_number' => $revision->revision_number,
        ]);
    }
}

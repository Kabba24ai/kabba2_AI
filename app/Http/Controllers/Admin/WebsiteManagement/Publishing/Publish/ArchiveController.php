<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\PagePublishService;

class ArchiveController extends Controller
{
    public function __construct(private PagePublishService $publishService) {}

    public function __invoke(string $page_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();
        $this->publishService->archive($page);

        return response()->json([
            'success'        => true,
            'message'        => 'Page archived.',
            'publish_status' => $page->publish_status,
        ]);
    }
}

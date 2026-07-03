<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Publishing\Publish;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsitePage;
use App\Services\Website\PagePublishService;
use Illuminate\Http\Request;

class AutoSaveController extends Controller
{
    public function __construct(private PagePublishService $publishService) {}

    public function __invoke(Request $request, string $page_unique_id)
    {
        $page = WebsitePage::where('unique_id', $page_unique_id)->firstOrFail();

        // data is optional — some builders send partial form state, others just ping
        $data = $request->input('data');

        $this->publishService->autoSave($page, is_array($data) ? $data : null);

        return response()->json([
            'success'  => true,
            'saved_at' => $page->auto_saved_at?->toIso8601String(),
            'label'    => 'Last saved ' . ($page->auto_saved_at?->diffForHumans() ?? 'just now'),
        ]);
    }
}

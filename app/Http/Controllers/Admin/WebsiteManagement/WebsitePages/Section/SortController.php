<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Section;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsiteSectionService;
use Illuminate\Http\Request;

class SortController extends Controller
{
    public function __construct(private WebsiteSectionService $sectionService) {}

    public function __invoke(Request $request)
    {
        $sections = $request->input('sections', []);

        if (empty($sections)) {
            return response()->json(['success' => false, 'message' => 'No sections provided.'], 422);
        }

        $this->sectionService->sort($sections);

        return response()->json(['success' => true]);
    }
}

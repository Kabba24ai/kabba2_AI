<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Item;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsiteSectionItemService;
use Illuminate\Http\Request;

class SortController extends Controller
{
    public function __construct(private WebsiteSectionItemService $itemService) {}

    public function __invoke(Request $request)
    {
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'No items provided.'], 422);
        }

        $this->itemService->sort($items);

        return response()->json(['success' => true]);
    }
}

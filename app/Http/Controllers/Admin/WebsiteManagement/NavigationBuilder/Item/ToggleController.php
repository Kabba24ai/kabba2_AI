<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use App\Services\Navigation\NavigationService;

class ToggleController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(string $unique_id)
    {
        $item   = WebsiteMenuItem::where('unique_id', $unique_id)->firstOrFail();
        $status = $item->status === 'Active' ? 'Inactive' : 'Active';
        $item->update(['status' => $status]);
        $this->navService->clearCache($item->menu->menu_key);

        return response()->json(['success' => true, 'status' => $status]);
    }
}

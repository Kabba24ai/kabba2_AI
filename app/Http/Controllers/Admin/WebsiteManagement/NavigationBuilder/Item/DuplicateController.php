<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteMenuItem;
use App\Services\Navigation\NavigationService;

class DuplicateController extends Controller
{
    public function __construct(private NavigationService $navService) {}

    public function __invoke(string $unique_id)
    {
        $item = WebsiteMenuItem::where('unique_id', $unique_id)->firstOrFail();

        $copy                = $item->replicate(['unique_id']);
        $copy->title         = $item->title . ' (Copy)';
        $copy->display_order = $item->display_order + 1;
        $copy->save();

        $this->navService->clearCache($item->menu->menu_key);

        return response()->json(['success' => true, 'item' => $copy->toBuilderArray()]);
    }
}

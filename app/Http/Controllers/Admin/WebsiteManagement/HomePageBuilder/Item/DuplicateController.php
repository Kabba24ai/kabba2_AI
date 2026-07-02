<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Services\Website\HomePageService;
use Illuminate\Http\Request;

class DuplicateController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $original = WebsiteSectionItem::where('unique_id', $unique_id)
            ->with('section')
            ->firstOrFail();

        $copy = $original->replicate(['unique_id', 'image']);
        $copy->title         = $original->title . ' (Copy)';
        $copy->display_order = $original->display_order + 1;
        $copy->image         = null;
        $copy->save();

        HomePageService::clearCache();

        $tab = $original->section?->section_key ?? 'hero';
        session()->flash('success', 'Item duplicated successfully.');
        return redirect()->route('admin.website-management.home-builder.index', ['tab' => $tab]);
    }
}

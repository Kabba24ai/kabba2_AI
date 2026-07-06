<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Services\Website\ContactPageService;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $item = WebsiteSectionItem::where('unique_id', $unique_id)->firstOrFail();
        $tab  = $item->section?->section_key ?? 'locations';
        $item->delete();

        ContactPageService::clearCache();
        session()->flash('success', 'Store removed successfully.');
        return redirect()->route('admin.website-management.contact-builder.index', ['tab' => $tab]);
    }
}

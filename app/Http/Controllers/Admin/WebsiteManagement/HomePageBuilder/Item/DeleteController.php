<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item;

use App\Http\Controllers\Controller;
use App\Models\WebsiteManagement\WebsiteSectionItem;
use App\Helpers\MediaHelper;
use App\Models\Global\Media;
use Illuminate\Http\Request;
use App\Services\Website\HomePageService;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Concerns\RedirectsToSectionEditor;

class DeleteController extends Controller
{
    use RedirectsToSectionEditor;

    public function __invoke(Request $request, string $unique_id)
    {
        $item = WebsiteSectionItem::where('unique_id', $unique_id)->firstOrFail();
        $tab  = $item->section?->section_key ?? 'hero';

        if ($item->image) {
            $media = Media::find($item->image);
            if ($media) MediaHelper::removeFile($media);
        }

        $item->delete();

        HomePageService::clearCache();
        session()->flash('success', 'Item deleted successfully.');
        return $this->redirectToSectionEditor($tab);
    }
}

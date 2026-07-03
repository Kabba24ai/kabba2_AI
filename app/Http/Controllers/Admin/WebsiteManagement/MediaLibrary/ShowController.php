<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Http\Controllers\Controller;
use App\Models\Global\Media;
use App\Services\MediaLibrary\MediaUsageService;

class ShowController extends Controller
{
    public function __construct(private MediaUsageService $usageService) {}

    public function __invoke(string $unique_id)
    {
        $media = Media::where('unique_id', $unique_id)->firstOrFail();

        return response()->json([
            'success'     => true,
            'media'       => $media->toLibraryArray(),
            'usage_count' => $this->usageService->getUsageCount($media),
        ]);
    }
}

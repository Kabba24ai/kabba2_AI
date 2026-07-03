<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Models\Global\Media;
use App\Services\MediaLibrary\MediaUsageService;

class DestroyController extends Controller
{
    public function __construct(private MediaUsageService $usageService) {}

    public function __invoke(string $unique_id)
    {
        $media = Media::where('unique_id', $unique_id)->firstOrFail();

        $usageCount = $this->usageService->getUsageCount($media);

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "This image is currently used in {$usageCount} location(s). Remove it from all usages before deleting.",
                'usage_count' => $usageCount,
            ], 409);
        }

        MediaHelper::removeFile($media);

        return response()->json(['success' => true]);
    }
}

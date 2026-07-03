<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Http\Controllers\Controller;
use App\Models\Global\Media;
use App\Services\MediaLibrary\MediaLibraryService;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(private MediaLibraryService $service) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $media = Media::where('unique_id', $unique_id)->firstOrFail();

        $validated = $request->validate([
            'alt_text'        => 'nullable|string|max:500',
            'title'           => 'nullable|string|max:255',
            'caption'         => 'nullable|string|max:500',
            'description'     => 'nullable|string|max:2000',
            'media_folder_id' => 'nullable|integer|exists:media_folders,id',
        ]);

        $this->service->updateMetadata($media, $validated);

        return response()->json([
            'success' => true,
            'media'   => $media->fresh()->toLibraryArray(),
        ]);
    }
}

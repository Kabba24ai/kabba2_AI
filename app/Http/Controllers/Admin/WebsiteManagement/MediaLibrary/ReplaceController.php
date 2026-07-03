<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Http\Controllers\Controller;
use App\Models\Global\Media;
use App\Services\MediaLibrary\MediaLibraryService;
use Illuminate\Http\Request;

class ReplaceController extends Controller
{
    public function __construct(private MediaLibraryService $service) {}

    public function __invoke(Request $request, string $unique_id)
    {
        $media = Media::where('unique_id', $unique_id)->firstOrFail();

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg|max:10240',
        ]);

        $media = $this->service->replace($media, $request->file('file'));

        return response()->json([
            'success' => true,
            'media'   => $media->toLibraryArray(),
        ]);
    }
}

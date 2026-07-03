<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Http\Controllers\Controller;
use App\Services\MediaLibrary\MediaLibraryService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private MediaLibraryService $service) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg|max:10240',
            'media_folder_id' => 'nullable|integer|exists:media_folders,id',
            'alt_text'        => 'nullable|string|max:500',
            'title'           => 'nullable|string|max:255',
        ]);

        try {
            $media = $this->service->upload($request->file('file'), [
                'media_folder_id' => $request->input('media_folder_id'),
                'alt_text'        => $request->input('alt_text'),
                'title'           => $request->input('title'),
            ]);

            return response()->json([
                'success' => true,
                'media'   => $media->toLibraryArray(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\Folder;

use App\Http\Controllers\Controller;
use App\Services\MediaLibrary\MediaLibraryService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private MediaLibraryService $service) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|integer|exists:media_folders,id',
        ]);

        $folder = $this->service->createFolder($validated['name'], $validated['parent_id'] ?? null);

        return response()->json([
            'success' => true,
            'folder'  => [
                'id'        => $folder->id,
                'unique_id' => $folder->unique_id,
                'name'      => $folder->name,
                'slug'      => $folder->slug,
                'parent_id' => $folder->parent_id,
            ],
        ], 201);
    }
}

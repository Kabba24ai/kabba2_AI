<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Http\Controllers\Controller;
use App\Services\MediaLibrary\MediaLibraryService;
use Illuminate\Http\Request;

class PickerController extends Controller
{
    public function __construct(private MediaLibraryService $service) {}

    public function __invoke(Request $request)
    {
        $filters = $request->only(['search', 'folder_id', 'type']);
        $perPage = (int) $request->input('per_page', 40);
        $media   = $this->service->list($filters, $perPage);
        $folders = $this->service->getFolders();

        return response()->json([
            'success' => true,
            'data'    => $media->getCollection()->map->toLibraryArray()->values(),
            'meta'    => [
                'current_page' => $media->currentPage(),
                'last_page'    => $media->lastPage(),
                'total'        => $media->total(),
                'per_page'     => $media->perPage(),
            ],
            'folders' => $folders->map(fn($f) => [
                'id'    => $f->id,
                'name'  => $f->name,
                'count' => $f->media_count,
            ])->values(),
        ]);
    }
}

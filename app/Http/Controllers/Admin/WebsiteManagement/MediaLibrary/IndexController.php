<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary;

use App\Http\Controllers\Controller;
use App\Services\MediaLibrary\MediaLibraryService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __construct(private MediaLibraryService $service) {}

    public function __invoke(Request $request)
    {
        $filters = $request->only(['search', 'folder_id', 'type']);
        $media   = $this->service->list($filters, 40);
        $folders = $this->service->getFolders();

        return view('admin.website_management.media_library.index', compact('media', 'folders', 'filters'));
    }
}

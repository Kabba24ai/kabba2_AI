<?php

namespace App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Tutorials\SystemLogicDocument;

class DestroyController extends Controller
{
    public function __invoke(int $id)
    {
        $doc = SystemLogicDocument::forModule('dispatch')->findOrFail($id);
        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logic note deleted.',
        ]);
    }
}

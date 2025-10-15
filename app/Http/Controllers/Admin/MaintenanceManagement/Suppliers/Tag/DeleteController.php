<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\SupplierTag;

class DeleteController extends Controller
{
    public function __invoke(SupplierTag $tag)
    {
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully!',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;
use App\Helpers\MediaHelper;

class DeleteController extends Controller
{
    public function __invoke(Supplier $supplier)
    {
        try {

            $mediaId = $supplier->media;
            if ($mediaId) {
                MediaHelper::removeFile($supplier->media);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier.'
            ], 500);
        }
    }
}

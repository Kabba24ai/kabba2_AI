<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\PartsList;

use App\Helpers\MediaHelper;

class DeleteController extends Controller
{
    public function __invoke(PartsList $list)
    {
        try {



            $list->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Template.'
            ], 500);
        }
    }
}

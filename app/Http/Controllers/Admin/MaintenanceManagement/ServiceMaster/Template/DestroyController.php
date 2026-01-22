<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;

class DestroyController extends Controller
{
    public function __invoke($id)
    {
        $template = ServiceTemplate::findOrFail($id);
        
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
        ]);
    }
}

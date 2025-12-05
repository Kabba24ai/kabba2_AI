<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\PartBrand;

class DeleteController extends Controller
{
    public function __invoke(PartBrand $brand)
    {
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'brand deleted successfully!',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\Brand\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\PartBrand;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();
        
        $brand = PartBrand::create([
            'name' =>   $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully!',
            'brand' => $brand,
        ]);

    }
}

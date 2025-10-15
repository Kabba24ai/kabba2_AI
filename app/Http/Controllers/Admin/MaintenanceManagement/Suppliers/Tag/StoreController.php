<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\SupplierTag;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        $tag = SupplierTag::create([
            'name' =>   $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully!',
            'tag' => $tag,
        ]);

    }
}

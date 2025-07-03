<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipments\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();
        $data['unique_id'] = Str::uuid();
        $data['status'] = 'available'; // Default status
        
        Equipment::create($data);
        
        return redirect()
            ->route('admin.maintenance-management.equipments.index')
            ->with('success', 'Equipment created successfully!');
    }
}

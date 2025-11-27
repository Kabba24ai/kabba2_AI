<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        $data['has_def'] = ($data['has_def'] ?? false) ? 'Yes' : 'No';
        $data['is_tracked'] = ($data['is_tracked'] ?? false) ? 'Yes' : 'No';

        Equipment::create($data);

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment created successfully!');

    }
}

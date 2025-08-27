<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();
        $data['unique_id'] = Str::uuid();
        $data['status'] = 'available'; // Default status
        //$data['has_def'] = $request->boolean('has_def') ? 'T' : 'F';

        Equipment::create($data);

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment created successfully!');

    }
}

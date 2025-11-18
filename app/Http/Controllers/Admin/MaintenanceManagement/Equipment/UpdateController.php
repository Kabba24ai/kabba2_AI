<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $data = $request->validated();
        $data['has_def'] = ($data['has_def'] ?? false) ? 'Yes' : 'No';
        $data['is_tracked'] = ($data['is_tracked'] ?? false) ? 'Yes' : 'No';

        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();


        $equipment->update($data);

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment updated successfully!');
    }
}

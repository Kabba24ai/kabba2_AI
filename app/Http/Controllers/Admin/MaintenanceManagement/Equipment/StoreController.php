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
        $data['not_for_rent'] = isset($data['not_for_rent']) ? 1 : 0;

        // If bring_service_flag is checked and bring_service_hour is not null, set equipment_hours to bring_service_hour
        if (isset($data['bring_service_flag']) && $data['bring_service_flag'] && isset($data['bring_service_hour']) && $data['bring_service_hour'] !== null) {
            $data['equipment_hours'] = $data['bring_service_hour'];
        }

        Equipment::create($data);

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment created successfully!');
 
    }
}
<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\StoreRequest;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Equipment;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        // Lookup equipment by ID
        $equipment = Equipment::find($data['equipment_id']);
  
        if (!$equipment) {
            return redirect()
                ->back()
                ->withErrors(['equipment_id' => 'Selected equipment does not exist.']);
        }

        // Fill in related fields
        $data['equipment_name'] = $equipment->equipment_name;
        $data['category'] = $equipment->category;

        $part = Part::create($data);

        return redirect()
            ->route('admin.maintenance-management.parts.index')
            ->with('success', 'Part created successfully.');
    }
}

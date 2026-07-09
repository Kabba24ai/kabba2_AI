<?php

namespace App\Http\Controllers\Admin\Warranty\Cases;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Warranty\WarrantyCase;

class CreateController extends Controller
{
    public function __invoke()
    {
        // Internal path: fleet units (identity auto-fills from the record).
        $equipmentOptions = Equipment::orderBy('equipment_name')
            ->get(['id', 'equipment_name', 'equipment_id', 'brand', 'serial_number'])
            ->map(fn (Equipment $equipment) => [
                'id'     => $equipment->id,
                'label'  => $equipment->equipment_name
                    . ($equipment->equipment_id ? ' (' . $equipment->equipment_id . ')' : ''),
                'brand'  => $equipment->brand,
                'serial' => $equipment->serial_number,
            ])->values();

        // External path: existing CRM customers only.
        $customers = Customer::orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'phone']);

        return view('admin.warranty.create', [
            'case'             => new WarrantyCase(),
            'equipmentOptions' => $equipmentOptions,
            'customers'        => $customers,
            'defaultFee'       => 95.00,
        ]);
    }
}

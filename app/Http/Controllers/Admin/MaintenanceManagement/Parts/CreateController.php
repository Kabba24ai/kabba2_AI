<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];

        $equipmentOptions = Equipment::select('id as id', 'equipment_name as name', 'category')
            ->orderBy('category')
            ->orderBy('equipment_name')
            ->get()
            ->toArray();
        $suppliers = [
            'Caterpillar Inc.',
            'Parker Hannifin',
            'Kohler Power',
            'Atlas Copco',
            'John Deere',
            'Industrial Supply Co.'
        ];

        return view('admin.maintenance_management.parts.create', compact('categories', 'equipmentOptions', 'suppliers'));
    }
}


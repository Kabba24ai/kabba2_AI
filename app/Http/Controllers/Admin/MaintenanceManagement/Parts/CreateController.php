<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];

        // $equipmentOptions = [
        //     ['id' => 'N/A', 'name' => 'General Use (Supplies)', 'category' => 'Supplies'],
        //     ['id' => 'EXC-001', 'name' => 'CAT 320D Excavator', 'category' => 'Excavators'],
        //     ['id' => 'EXC-002', 'name' => 'John Deere 350G Excavator', 'category' => 'Excavators'],
        //     ['id' => 'GEN-045', 'name' => 'Kohler 150kW Generator', 'category' => 'Generators'],
        //     ['id' => 'GEN-046', 'name' => 'Cummins 200kW Generator', 'category' => 'Generators'],
        //     ['id' => 'BUL-012', 'name' => 'John Deere 650K Dozer', 'category' => 'Bulldozers'],
        //     ['id' => 'BUL-013', 'name' => 'CAT D6T Dozer', 'category' => 'Bulldozers'],
        //     ['id' => 'LDR-023', 'name' => 'CAT 950 Wheel Loader', 'category' => 'Loaders'],
        //     ['id' => 'LDR-024', 'name' => 'John Deere 644K Loader', 'category' => 'Loaders'],
        //     ['id' => 'CMP-078', 'name' => 'Atlas Copco GA30', 'category' => 'Compressors'],
        //     ['id' => 'CMP-079', 'name' => 'Ingersoll Rand R55', 'category' => 'Compressors']
        // ];
        $equipmentOptions = Equipment::select('equipment_id as id', 'equipment_name as name', 'category')
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


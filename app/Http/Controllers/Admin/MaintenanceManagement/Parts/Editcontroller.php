<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\Part\Part;

class EditController extends Controller
{
    public function __invoke(Part $unique_id)
    {
        $part = $unique_id; // Laravel route model binding

        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];
        $equipmentOptions = [
            ['id' => 'N/A', 'name' => 'General Use (Supplies)', 'category' => 'Supplies'],
            ['id' => 'EXC-001', 'name' => 'CAT 320D Excavator', 'category' => 'Excavators'],
            // ... rest of equipment options
        ];

        $suppliers = [
            'Caterpillar Inc.',
            'Parker Hannifin',
            'Kohler Power',
            'Atlas Copco',
            'John Deere',
            'Industrial Supply Co.'
        ];

        return view('admin.maintenance_management.parts.edit', compact('part', 'categories', 'equipmentOptions', 'suppliers'));
    }
}

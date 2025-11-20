<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Supplier;

class EditController extends Controller
{
    public function __invoke( $unique_id)
    {

        $part = Part::where('unique_id', $unique_id)->first(); // Laravel route model binding

        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];
        $equipmentOptions = [
            ['id' => 'N/A', 'name' => 'General Use (Supplies)', 'category' => 'Supplies'],
            ['id' => 'EXC-001', 'name' => 'CAT 320D Excavator', 'category' => 'Excavators'],
            // ... rest of equipment options
        ];

        $suppliers = Supplier::orderBy('name')->get(['unique_id', 'name']);


        // dd($part);

        return view('admin.maintenance_management.parts.edit', compact('part', 'categories', 'equipmentOptions', 'suppliers'));
    }
}

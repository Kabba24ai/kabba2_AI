<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\PartsList;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];

        // $equipmentOptions = Equipment::select('id as id', 'equipment_name as name', 'category')
        //     ->orderBy('category')
        //     ->orderBy('equipment_name')
        //     ->get()
        //     ->toArray();

        $suppliers = Supplier::orderBy('name')->get(['unique_id', 'name']);

        $list = PartsList::get();

        return view('admin.maintenance_management.parts.create', compact('categories', 'suppliers','list'));
    }
}


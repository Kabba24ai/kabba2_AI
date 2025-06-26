<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipments;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;

class EditController extends Controller
{
    public function __invoke($unique_id)
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();
        
        $categories = [
            'Excavators',
            'Bulldozers',
            'Loaders',
            'Generators',
            'Compressors',
            'Trucks',
            'Trailers',
            'Concrete Equipment',
            'Lifting Equipment',
            'Hand Tools'
        ];
        
        return view('admin.maintenance_management.equipments.edit', compact('equipment', 'categories'));
    }
}

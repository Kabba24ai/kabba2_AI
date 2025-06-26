<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipments;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;

class CreateController extends Controller
{
    public function __invoke()
    {
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

        $equipment = new Equipment();
        return view('admin.maintenance_management.equipments.create', compact('categories', 'equipment'));
    }
}

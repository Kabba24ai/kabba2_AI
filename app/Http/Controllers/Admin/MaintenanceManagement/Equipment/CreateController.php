<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ProductCategory::select('title')->get();

        $equipmentServiceList = [
            'Excavator Maintenance',
            'Generator Service',
            'Dozer Maintenance',
            'Loader Service',
            'Compressor Service',
            'Basic Maintenance',
            'Heavy Equipment Service'
        ];

        $equipmentPartsList = [
            'Excavator Standard Parts',
            'Generator Parts Kit',
            'Dozer Parts List',
            'Loader Parts Template',
            'Compressor Parts Kit',
            'Basic Parts List'
        ];

        $customerChecklist = [
            'Equipment Delivery Checklist',
            'Equipment Return Checklist',
            'Customer Handoff Checklist',
            'Damage Assessment Checklist',
            'Basic Customer Checklist'
        ];

        $equipment = new Equipment();
        return view('admin.maintenance_management.equipment.create', compact('categories', 'equipment', 'equipmentServiceList', 'equipmentPartsList','customerChecklist'));
    }
}

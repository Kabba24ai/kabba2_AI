<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;

class FetchController extends Controller
{
    public function __invoke()
    {
        $equipments = Equipment::notRented()->orderBy('equipment_name')->get()->map(function ($equipment) {
            $linkWithTitle = $equipment->linkWithTitle();
            return [
                'unique_id' => $equipment->unique_id,
                'equipment_name' => $equipment->equipment_name,
                'current_status' => $equipment->current_status,
                'link' => $linkWithTitle['link'],
                'link_title' => $linkWithTitle['title'],
            ];
        });
        return response()->json(
            [
                'success' => true,
                'equipments' => $equipments
            ]
        );
    }
}

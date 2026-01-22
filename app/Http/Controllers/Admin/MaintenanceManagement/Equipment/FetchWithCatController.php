<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;

class FetchWithCatController extends Controller
{
    public function __invoke()
    {
        $categories = ProductCategory::with([
            'equipments' => function ($q) {
                $q->where('not_for_rent', 0)->orderBy('equipment_name')->orderBy('equipment_id');
            },
        ])
            ->orderBy('title')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'equipments' => $cat->equipments->map(function ($equipment) {
                        $linkWithTitle = $equipment->linkWithTitle();
                        return [
                            'unique_id' => $equipment->unique_id,
                            'equipment_name' => $equipment->equipment_name,
                            'current_status' => $equipment->current_status,
                            'link' => $linkWithTitle['link'],
                            'link_title' => $linkWithTitle['title'],
                            'equipment_id' => $equipment->equipment_id,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }
}

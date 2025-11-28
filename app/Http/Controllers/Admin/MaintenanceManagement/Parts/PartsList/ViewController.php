<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\PartsList;

class ViewController extends Controller
{
    public function __invoke($unique_id)
    {
        // Load the parts list with creator, category, and parts
        $list = PartsList::with(['creator', 'category', 'parts.category', 'parts.primarySupplier'])->where('unique_id', $unique_id)->firstOrFail();

        // Load all parts for reference (if needed for filters)
        $parts = Part::with('category')->get();

        // Compute total template value
        $totalValue = $list->parts->sum(function ($part) {
            return $part->primary_part_cost ?? 0;
        });

        return view('admin.maintenance_management.parts.parts_list.view', compact(
            'list',
            'parts',
            'totalValue'
        ));
    }
}


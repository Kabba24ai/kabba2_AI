<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Supplier;

class FetchPartController extends Controller
{
    public function __invoke($id)
    {
        $part = Part::where('unique_id', $id)->first();

        if(!$part){
        return response()->json([
            'status' => 'error',
            'message' => 'Part not found'
        ], 404);
    }

        return response()->json([
        'status' => 'success',
        'message' => 'Part details loaded',
        'part' => [
            'primary_part_number' => $part->primary_part_number,
            'primary_cost' => $part->primary_part_cost,
        ]
    ]);
    }
}

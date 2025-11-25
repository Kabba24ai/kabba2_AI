<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Supplier;
use Illuminate\Http\Request;

class AssignPartController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
        'supplier_unique_id' => 'required',
        'part_unique_id' => 'required',
        'part_number' => 'required',
        'cost' => 'required'
    ]);

    $part = Part::where('unique_id', $request->part_unique_id)->first();

    if (!$part) {
        return response()->json(['status' => 'error', 'message' => 'Part not found'], 404);
    }

    // Update part
    $part->primary_part_number = $request->part_number;
    $part->primary_part_cost = $request->cost;
    $part->primary_part_supplier_id = $request->supplier_unique_id;
    $part->save();

    return response()->json(['status' => 'success', 'message' => 'Part updated successfully!']);
    }
}

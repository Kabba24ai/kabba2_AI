<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use Illuminate\Http\Request;

class UpdateCostController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            // Validate input
            $request->validate([
    'part_id' => 'required|exists:parts,id',
    'field' => 'required|in:primary_part_cost,alt_1_part_cost,alt_2_part_cost',
    'cost' => 'required|numeric|min:0',
]);


            // Find part and update cost
            $part = Part::findOrFail($request->part_id);
            $part->{$request->field} = $request->cost;
            $part->save();

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Cost updated successfully!',
                'cost' => number_format($part->{$request->field}, 2)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Return general error
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}

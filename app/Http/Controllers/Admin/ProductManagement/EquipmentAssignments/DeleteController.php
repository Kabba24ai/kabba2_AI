<?php

namespace App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductEquipmentAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function __invoke(Request $request, ProductEquipmentAssignment $equipmentAssignment): JsonResponse|RedirectResponse
    {
        $equipmentAssignment->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment assignment deleted successfully.',
            ]);
        }

        return redirect()
            ->route('admin.product-management.equipment-assignments.index')
            ->with('success', 'Equipment assignment deleted successfully.');
    }
}

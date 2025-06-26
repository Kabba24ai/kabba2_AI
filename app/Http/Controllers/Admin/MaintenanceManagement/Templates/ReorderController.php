<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class ReorderPartsController extends Controller
{
    public function __invoke(Request $request, Template $unique_id)
    {
        $request->validate([
            'parts' => 'required|array',
            'parts.*' => 'exists:parts,id'
        ]);

        $partsWithOrder = [];
        foreach ($request->parts as $index => $partId) {
            $partsWithOrder[$partId] = ['sort_order' => $index + 1];
        }

        $unique_id->parts()->sync($partsWithOrder);

        return response()->json([
            'success' => true,
            'message' => 'Parts reordered successfully.'
        ]);
    }
}

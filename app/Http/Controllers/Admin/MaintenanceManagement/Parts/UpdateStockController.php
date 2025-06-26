<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use Illuminate\Http\Request;

class UpdateStockController extends Controller
{
    public function __invoke(Request $request, Part $unique_id)
    {
        $request->validate([
            'stock_level' => 'required|integer|min:0'
        ]);

        $unique_id->update([
            'stock_level' => $request->stock_level
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock level updated successfully.',
            'stock_status' => $unique_id->stock_status
        ]);
    }
}

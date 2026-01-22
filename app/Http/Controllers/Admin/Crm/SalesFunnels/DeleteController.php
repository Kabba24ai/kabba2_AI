<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnel;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $funnel = SalesFunnel::where('unique_id', $unique_id)->first();

        if (! $funnel) {
            return response()->json([
                'success' => false,
                'message' => 'Sales funnel not found.',
            ], 404);
        }

        $funnel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sales funnel deleted successfully.',
        ]);
    }
}

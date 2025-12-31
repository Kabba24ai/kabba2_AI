<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Steps;

use App\Http\Controllers\Controller;

// Models
use App\Models\Customers\SalesFunnelSteps;

class DeleteController extends Controller
{
    public function __invoke($stepUniqueId)
    {
        try {
            $step = SalesFunnelSteps::where('unique_id', $stepUniqueId)->firstOrFail();
            $step->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sales funnel step: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sales funnel step deleted successfully.',
        ]);
    }
}

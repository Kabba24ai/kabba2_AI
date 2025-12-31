<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SalesFunnel;

class StatusController extends Controller
{
    public function __invoke(Request $request, $unique_id)
    {
        try {
            $funnel = SalesFunnel::where('unique_id', $unique_id)->firstOrFail();
            $funnel->status = $funnel->status === 'Active' ? 'Inactive' : 'Active';
            $funnel->save();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update funnel status.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'status'  => $funnel->status,
            'message' => 'Funnel status updated successfully.',
        ]);
    }
}

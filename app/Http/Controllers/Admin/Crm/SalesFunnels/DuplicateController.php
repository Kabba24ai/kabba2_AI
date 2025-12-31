<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\SalesFunnel;

class DuplicateController extends Controller
{
    public function __invoke(Request $request, $unique_id)
    {
        try {
            $funnel = SalesFunnel::where('unique_id', $unique_id)->firstOrFail();

            $newFunnel = $funnel->replicate();
            $newFunnel->funnel_name = $funnel->funnel_name . ' (Copy)';
            $newFunnel->status = 'Inactive';
            $newFunnel->save();

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Funnel not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Duplicate funnel created successfully.',
        ]);
    }
}

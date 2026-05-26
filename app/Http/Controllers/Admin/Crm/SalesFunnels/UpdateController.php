<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnel;
use App\Http\Requests\Admin\Crm\SalesFunnels\UpdateRequest;

class UpdateController extends Controller
{
    public function __invoke(string $unique_id, UpdateRequest $request)
    {
        $funnel = SalesFunnel::where('unique_id', $unique_id)->first();

        if (! $funnel) {
            return response()->json([
                'success' => false,
                'message' => 'Sales funnel not found.',
            ], 404);
        }

        $funnel->update([
            'funnel_name'              => $request->name,
            'description'              => $request->description,
            'sales_funnel_category_id' => $request->category_id,
            'trigger_type'             => $request->trigger_type,
            'trigger_reference'        => $request->trigger_reference,
            'status'                   => $request->is_active ? 'Active' : 'Inactive',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sales funnel updated successfully.',
        ]);
    }
}


<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels;

use App\Http\Controllers\Controller;
use App\Models\Customers\SalesFunnel;
use App\Http\Requests\Admin\Crm\SalesFunnels\StoreRequest;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        try {
            $funnel = SalesFunnel::create([
                'funnel_name'              => $request->name,
                'description'              => $request->description,
                'sales_funnel_category_id' => $request->category_id,
                'trigger_type'             => $request->trigger_type,
                'status'                   => $request->is_active ? 'Active' : 'Inactive',
            ]);

            Log::info('Sales funnel created', [
                'funnel_id'  => $funnel->id,
                'unique_id'  => $funnel->unique_id,
                'created_by' => auth()->id(),
                'payload'    => $request->validated(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sales funnel created successfully.',
                'data'    => $funnel,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to create sales funnel', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sales funnel.',
            ], 500);
        }
    }
}

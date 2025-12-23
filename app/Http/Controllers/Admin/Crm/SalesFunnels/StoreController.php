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
            $triggerEventMap = [
                'rental_start_date' => 'Rental Start Date',
                'new_lead_added'    => 'New Lead Added',
            ];

            $timingMap = [
                'before' => 'Before Event',
                'after'  => 'After Event',
            ];

            $funnel = SalesFunnel::create([
                'funnel_name'              => $request->name,
                'description'              => $request->description,
                'sales_funnel_category_id' => $request->category_id,
                'trigger_event'            => $triggerEventMap[$request->trigger_event] ?? null,
                'trigger_event_timing'     => $timingMap[$request->timing] ?? null,
                'date_value'               => $request->date_value,
                'hour_value'               => $request->hour_value,
                'minute_value'             => $request->minute_value,
                'status'                   => $request->is_active ? 'Active' : 'Inactive',
            ]);

            //  SUCCESS LOG
            Log::info('Sales funnel created', [
                'funnel_id'   => $funnel->id,
                'unique_id'   => $funnel->unique_id,
                'created_by' => auth()->id(),
                'payload'    => $request->validated(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sales funnel created successfully.',
                'data'    => $funnel,
            ]);
        } catch (\Throwable $e) {
  // ERROR LOG
            Log::error('Failed to create sales funnel', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->all(),
                'user_id'=> auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sales funnel.',
            ], 500);
        }
    }
}

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

        $triggerEventMap = [
            'rental_start_date' => 'Rental Start Date',
            'new_lead_added'    => 'New Lead Added',
        ];

        $timingMap = [
            'before' => 'Before Event',
            'after'  => 'After Event',
        ];

        $funnel->update([
            'funnel_name'              => $request->name,
            'description'              => $request->description,
            'sales_funnel_category_id' => $request->category_id,
            'trigger_event'            => $triggerEventMap[$request->trigger_event],
            'trigger_event_timing'     => $timingMap[$request->timing],
            'date_value'               => $request->date_value,
            'hour_value'               => $request->hour_value,
            'minute_value'             => $request->minute_value,
            'status'                   => $request->is_active ? 'Active' : 'Inactive',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sales funnel updated successfully.',
        ]);
    }
}


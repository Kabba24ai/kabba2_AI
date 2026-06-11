<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Customers\CustomerCallNeededActivity;
use App\Http\Requests\Admin\Dashboard\CallNeededCompleteRequest;

class CallNeededCompleteController extends Controller
{
    public function __invoke(
            CallNeededCompleteRequest $request,
            $id
        )
    {
       

        $call = CustomerCallNeeded::findOrFail($id);

        CustomerCallNeededActivity::create([
            'customer_call_needed_id' => $call->id,
            'status' => $request->call_status,
            'notes' => $request->completion_note,
            'follow_up_date' => now(),
            'created_by' => auth()->id(),
        ]);

        if ($request->action === 'complete') {

            $call->update([
                'status' => 'clear',
            ]);

            if ($call->customer) {

                $statusLabel = ucwords(str_replace('_', ' ', $request->call_status));

                $description = "Call completed. Action: {$statusLabel}.";

                if ($request->filled('completion_note')) {
                    $description .= " Notes: {$request->completion_note}";
                }

                $call->customer->notes()->create([
                    'customer_call_needed_id' => $call->id,
                    'description'             => $description,
                    'created_by'              => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Call completed successfully.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Call activity saved successfully.',
        ]);
    }
}
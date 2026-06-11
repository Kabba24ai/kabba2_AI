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

                    $note = $call->customer->notes()
                        ->where('customer_call_needed_id', $call->id)
                        ->latest('id')
                        ->first();

                    if ($note) {

                        $note->update([
                            'description' => $note->description .
                                "\n\n-- Call Completed (" .
                                now()->format('m/d/Y h:i A') .
                                ")",
                        ]);
                    }
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
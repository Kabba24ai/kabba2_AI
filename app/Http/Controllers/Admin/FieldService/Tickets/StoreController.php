<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FieldService\SaveFieldTicketRequest;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\Orders\Order;

class StoreController extends Controller
{
    public function __invoke(SaveFieldTicketRequest $request)
    {
        $validated = $request->validated();

        // The Order stays the source of truth — customer is derived from
        // the selected order, never entered separately.
        $validated['customer_id'] = !empty($validated['order_id'])
            ? Order::find($validated['order_id'])?->customer_id
            : null;

        foreach (['photos_received', 'video_received', 'media_reviewed', 'additional_media_required', 'media_bypassed'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        $ticket = FieldServiceTicket::create($validated + [
            'created_by' => auth()->id(),
        ]);

        flash('Field service ticket ' . $ticket->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}

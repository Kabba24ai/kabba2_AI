<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Deposit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveDepositRequest;
use App\Models\Service\ServiceTicket;

class UpdateController extends Controller
{
    /** Save parts-deposit state — tracking only, no payment is processed. */
    public function __invoke(SaveDepositRequest $request, ServiceTicket $ticket)
    {
        $ticket->fill($request->validated());

        if ($ticket->parts_deposit_paid && !$ticket->parts_deposit_paid_at) {
            $ticket->parts_deposit_paid_at = now();
        }
        if (!$ticket->parts_deposit_paid) {
            $ticket->parts_deposit_paid_at = null;
        }

        $ticket->updated_by = auth()->id();
        $ticket->save();

        flash('Parts deposit updated for ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

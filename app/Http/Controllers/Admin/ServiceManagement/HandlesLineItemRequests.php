<?php

namespace App\Http\Controllers\Admin\ServiceManagement;

use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

/**
 * Shared post-change handling for ticket line items (labor, charges, parts):
 * requests coming from the Settlement Preview redirect back to it and log a
 * Settlement Updated timeline event, since the totals just recalculated.
 */
trait HandlesLineItemRequests
{
    private function afterLineChange(Request $request, ServiceTicket $ticket, string $what)
    {
        if ($request->input('from') === 'settlement') {
            $ticket->recordSettlementUpdated($what);

            return redirect()->route('admin.service-management.tickets.settlement.preview', $ticket);
        }

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

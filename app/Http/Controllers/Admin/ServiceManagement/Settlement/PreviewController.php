<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Settlement;

use App\Enums\Service\ServiceTicketEventType;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketEvent;
use App\Services\ServiceManagement\SettlementPackage;

class PreviewController extends Controller
{
    /** Final manager review before any financial records are created. */
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->load(['laborEntries.employee', 'chargeLines', 'partsUsed', 'order', 'customer', 'equipment']);

        $package    = SettlementPackage::fromTicket($ticket);
        $settlement = $ticket->activeSettlement();

        // Log the first preview only — repeat visits are not new events.
        if (!$settlement && !$ticket->events()->where('event_type', ServiceTicketEventType::SettlementPreviewGenerated->value)->exists()) {
            ServiceTicketEvent::record($ticket->id, ServiceTicketEventType::SettlementPreviewGenerated);
        }

        return view('admin.service_management.tickets.settlement', compact('ticket', 'package', 'settlement'));
    }
}

<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;

/**
 * Final Send Wizard page — the ONLY place actual sending authorization
 * happens. Available for Awaiting Confirmation (first authorization) and
 * Scheduled (review / change timing) broadcasts.
 */
class SendWizardController extends Controller
{
    public function __invoke(int $id)
    {
        $event = SmsBroadcastEvent::with(['createdBy'])->findOrFail($id);

        abort_unless(in_array($event->status, [
            SmsBroadcastStatus::AwaitingConfirmation,
            SmsBroadcastStatus::Scheduled,
        ], true), 404);

        return view('admin.crm.message_management.broadcast_queue.send', [
            'event' => $event,
        ]);
    }
}

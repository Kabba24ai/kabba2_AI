<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use App\Services\Crm\SmsBroadcastService;

/**
 * Cancel a queued or scheduled broadcast before processing begins.
 * Cancelled broadcasts remain part of the historical record.
 */
class CancelController extends Controller
{
    public function __invoke(SmsBroadcastService $service, int $id)
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        if (!in_array($event->status, [SmsBroadcastStatus::AwaitingConfirmation, SmsBroadcastStatus::Scheduled], true)) {
            return redirect()->back()->with('error', 'This broadcast can no longer be cancelled.');
        }

        $service->cancel($event);

        return redirect()->back()->with('success', "Broadcast \"{$event->name}\" cancelled.");
    }
}

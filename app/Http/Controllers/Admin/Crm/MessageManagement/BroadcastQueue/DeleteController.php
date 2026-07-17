<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use Illuminate\Http\Request;

/**
 * Permanent deletion is limited: Draft and Awaiting Confirmation may be
 * deleted normally; Scheduled only via an explicit destructive flag.
 * Sending / Sent / Partially Sent / Failed / Cancelled are historical
 * business records and cannot be deleted here.
 */
class DeleteController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        $allowed = $event->status->isDeletable()
            || ($event->status === SmsBroadcastStatus::Scheduled && $request->boolean('destructive'));

        if (!$allowed) {
            return redirect()->back()->with('error', 'This broadcast is a historical record and cannot be deleted.');
        }

        $event->delete(); // recipients cascade

        return redirect()->route('admin.crm.message-management.broadcast-queue.index')
            ->with('success', "Broadcast \"{$event->name}\" deleted.");
    }
}

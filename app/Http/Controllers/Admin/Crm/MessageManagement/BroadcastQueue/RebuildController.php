<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use App\Services\Crm\SmsBroadcastService;
use Illuminate\Http\Request;

/**
 * Explicit edit of a queued/scheduled broadcast's message or audience:
 * the prepared package is discarded, the event returns to Draft, and the
 * wizard reopens — audience review and final confirmation are required
 * again. The UI warns before calling this.
 */
class RebuildController extends Controller
{
    public function __invoke(Request $request, SmsBroadcastService $service, int $id)
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        if (!in_array($event->status, [SmsBroadcastStatus::AwaitingConfirmation, SmsBroadcastStatus::Scheduled], true)
            && $event->status !== SmsBroadcastStatus::Draft) {
            return redirect()->back()->with('error', 'This broadcast can no longer be edited.');
        }

        if ($event->status !== SmsBroadcastStatus::Draft) {
            $service->revertToDraft($event, (int) $request->input('step', 1));
        }

        return redirect()->route('admin.crm.message-management.broadcast-wizard.create', ['event' => $event->id]);
    }
}

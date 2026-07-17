<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use Illuminate\Http\Request;

/**
 * Schedule-only edit: changing just the date/time never rebuilds the
 * message snapshot or the frozen recipient package.
 */
class RescheduleController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        if ($event->status !== SmsBroadcastStatus::Scheduled) {
            return redirect()->back()->with('error', 'Only scheduled broadcasts can be rescheduled.');
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $event->update(['scheduled_at' => $data['scheduled_at']]);

        return redirect()->back()->with(
            'success',
            "Broadcast rescheduled for " . $event->fresh()->scheduled_at->format('M j, Y g:i A') . '.',
        );
    }
}

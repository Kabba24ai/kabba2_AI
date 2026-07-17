<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use App\Services\Crm\SmsBroadcastService;
use Illuminate\Http\Request;

/**
 * Final Send Wizard confirmation — Send Broadcast Now or Schedule
 * Broadcast. This is the single point where transmission of the frozen
 * package is authorized.
 */
class AuthorizeSendController extends Controller
{
    public function __invoke(Request $request, SmsBroadcastService $service, int $id)
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        if (!in_array($event->status, [SmsBroadcastStatus::AwaitingConfirmation, SmsBroadcastStatus::Scheduled], true)) {
            return redirect()->route('admin.crm.message-management.broadcast-queue.index')
                ->with('error', 'This broadcast is not awaiting send authorization.');
        }

        $data = $request->validate([
            'timing'       => ['required', 'in:now,scheduled'],
            'scheduled_at' => ['required_if:timing,scheduled', 'nullable', 'date', 'after:now'],
        ]);

        if ($data['timing'] === 'now') {
            $service->sendNow($event);

            return redirect()->route('admin.crm.message-management.broadcast-queue.index')
                ->with('success', "Broadcast \"{$event->name}\" is sending to {$event->recipient_count} recipients.");
        }

        $service->schedule($event, $data['scheduled_at']);

        return redirect()->route('admin.crm.message-management.broadcast-queue.index')
            ->with('success', "Broadcast \"{$event->name}\" scheduled for " . $event->fresh()->scheduled_at->format('M j, Y g:i A') . " ({$event->recipient_count} recipients).");
    }
}

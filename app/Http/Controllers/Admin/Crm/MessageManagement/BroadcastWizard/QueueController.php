<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastWizard;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use App\Services\Crm\SmsBroadcastService;
use Illuminate\Http\JsonResponse;

/**
 * Step 5 "Add to Send Queue": freezes the prepared package (message
 * snapshot + recipient rows) and moves the draft to Awaiting
 * Confirmation. Transmits NOTHING — sending authorization happens only
 * in the separate Final Send Wizard.
 */
class QueueController extends Controller
{
    public function __invoke(SmsBroadcastService $service, int $id): JsonResponse
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        if ($event->status !== SmsBroadcastStatus::Draft) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft broadcasts can be added to the send queue.',
            ], 422);
        }

        if (!$event->sms_broadcast_id && !$event->message_content) {
            return response()->json(['success' => false, 'message' => 'Choose a message first.'], 422);
        }

        $stats = $service->queueEvent($event);

        if ($stats['final_count'] === 0) {
            $service->revertToDraft($event, 3);

            return response()->json([
                'success' => false,
                'message' => 'No eligible recipients match this audience — adjust the audience and try again.',
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'event_id' => $event->id,
            'stats'    => $stats,
        ]);
    }
}

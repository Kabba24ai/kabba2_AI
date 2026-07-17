<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;

class ShowController extends Controller
{
    public function __invoke(int $id)
    {
        $event = SmsBroadcastEvent::with(['createdBy', 'message'])->findOrFail($id);

        $recipientSummary = $event->recipients()
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $failedRecipients = $event->recipients()
            ->where('status', 'failed')
            ->orderBy('id')
            ->limit(50)
            ->get();

        return view('admin.crm.message_management.broadcast_queue.show', [
            'event'            => $event,
            'recipientSummary' => $recipientSummary,
            'failedRecipients' => $failedRecipients,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;

/**
 * Manual archive of a completed or cancelled broadcast — record keeps
 * its frozen content and results, it just leaves the active queue view.
 */
class ArchiveController extends Controller
{
    public function __invoke(int $id)
    {
        $event = SmsBroadcastEvent::findOrFail($id);

        if (!$event->status->isCompleted()) {
            return redirect()->back()->with('error', 'Only completed or cancelled broadcasts can be archived.');
        }

        $event->update(['archived_at' => now()]);

        return redirect()->back()->with('success', "Broadcast \"{$event->name}\" archived.");
    }
}

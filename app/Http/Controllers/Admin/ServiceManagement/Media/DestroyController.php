<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Media;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketMedia;

class DestroyController extends Controller
{
    public function __invoke(ServiceTicket $ticket, ServiceTicketMedia $media)
    {
        abort_unless($media->service_ticket_id === $ticket->id, 404);

        // Remove the physical file + central media row, then soft-delete the
        // ticket-side record (keeps the timeline/audit trail intact).
        if ($media->mediaAsset) {
            MediaHelper::removeFile($media->mediaAsset);
        }
        $media->delete();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('File removed.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

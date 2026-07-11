<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Media;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketMedia;
use Illuminate\Support\Facades\Storage;

class DestroyController extends Controller
{
    public function __invoke(ServiceTicket $ticket, ServiceTicketMedia $media)
    {
        abort_unless($media->service_ticket_id === $ticket->id, 404);

        // Remove the physical file (central media row + public_asset disk
        // for legacy rows, or the dedicated service_media disk for new
        // ones), then soft-delete the ticket-side record (keeps the
        // timeline/audit trail intact).
        if ($media->mediaAsset) {
            MediaHelper::removeFile($media->mediaAsset);
        } elseif ($media->file_path) {
            Storage::disk($media->disk ?: 'public_asset')->delete($media->file_path);
        }
        $media->delete();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('File removed.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

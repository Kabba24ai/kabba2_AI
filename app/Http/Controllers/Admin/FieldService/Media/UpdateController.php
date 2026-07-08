<?php

namespace App\Http\Controllers\Admin\FieldService\Media;

use App\Enums\FieldService\FieldTicketEventType;
use App\Http\Controllers\Controller;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\FieldService\FieldServiceTicketEvent;
use Illuminate\Http\Request;

/** Updates the media-review checklist flags from the workbench. */
class UpdateController extends Controller
{
    public function __invoke(Request $request, FieldServiceTicket $ticket)
    {
        $flags = ['photos_received', 'video_received', 'media_reviewed', 'additional_media_required', 'media_bypassed'];

        $request->validate(array_fill_keys($flags, ['nullable', 'boolean']));

        foreach ($flags as $flag) {
            $ticket->{$flag} = $request->boolean($flag);
        }
        $ticket->save();

        FieldServiceTicketEvent::record(
            $ticket->id,
            FieldTicketEventType::MediaUpdated,
            new: collect($flags)->filter(fn ($flag) => $ticket->{$flag})
                ->map(fn ($flag) => ucfirst(str_replace('_', ' ', $flag)))
                ->implode(', ') ?: 'All flags cleared',
        );

        flash('Media checklist updated.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}

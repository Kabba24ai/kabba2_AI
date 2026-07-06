<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Media;

use App\Enums\Service\ServiceMediaType;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveMediaRequest;
use App\Models\Service\ServiceTicket;

class StoreController extends Controller
{
    public function __invoke(SaveMediaRequest $request, ServiceTicket $ticket)
    {
        $file = $request->file('file');

        // Physical storage through the standard Kabba media pipeline.
        $uploaded = MediaHelper::uploadStorageFile('Public Asset', $file, 'service_tickets', $ticket);
        $mediaObj = $uploaded['mediaObj'] ?? null;

        if (!$mediaObj) {
            flash('Upload failed — the file could not be stored.')->error();

            return redirect()->route('admin.service-management.tickets.show', $ticket);
        }

        $ticket->media()->create([
            'media_id'          => $mediaObj->id,
            'media_type'        => ServiceMediaType::fromMime($mediaObj->mime_type, $mediaObj->file_extension),
            'category'          => $request->validated('category'),
            'file_path'         => $mediaObj->getFilePath(),
            'original_filename' => $mediaObj->original_file_name,
            'mime_type'         => $mediaObj->mime_type,
            'file_size'         => $mediaObj->file_size,
            'uploaded_by'       => auth()->id(),
            'notes'             => $request->validated('notes'),
        ]);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('File uploaded to ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

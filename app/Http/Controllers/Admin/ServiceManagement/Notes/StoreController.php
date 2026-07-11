<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Notes;

use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceMediaWorkflowStage;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'note'    => ['required', 'string', 'max:5000'],
            'media'   => ['nullable', 'array'],
            'media.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm'],
        ]);

        $note = $ticket->notes()->create([
            'note'       => $validated['note'],
            'created_by' => auth()->id(),
        ]);

        // Photos/video attached to this specific note — dedicated Service
        // Module media repository, linked via the note's attachable morph.
        foreach ($request->file('media', []) as $file) {
            $uploaded = MediaHelper::uploadServiceMediaFile($file, ServiceMediaWorkflowStage::Notes, $ticket);
            if (!$uploaded) {
                continue;
            }

            $note->media()->create([
                'service_ticket_id' => $ticket->id,
                'media_type'        => ServiceMediaType::fromMime($uploaded['mime_type'], $uploaded['file_extension']),
                'category'          => ServiceMediaCategory::Note->value,
                'workflow_stage'    => ServiceMediaWorkflowStage::Notes->value,
                'retention_class'   => ServiceMediaWorkflowStage::Notes->defaultRetentionClass()->value,
                'disk'              => 'service_media',
                'file_path'         => $uploaded['file_path'],
                'original_filename' => $uploaded['original_filename'],
                'mime_type'         => $uploaded['mime_type'],
                'file_size'         => $uploaded['file_size'],
                'uploaded_by'       => auth()->id(),
            ]);
        }

        flash('Note added to ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

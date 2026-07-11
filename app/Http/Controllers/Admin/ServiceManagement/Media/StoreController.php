<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Media;

use App\Enums\Service\ServiceMediaCategory;
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
        $category = ServiceMediaCategory::from($request->validated('category'));
        $stage = $category->workflowStage();

        // Physical storage on the dedicated Service Module media repository.
        $uploaded = MediaHelper::uploadServiceMediaFile($file, $stage, $ticket);

        if (!$uploaded) {
            flash('Upload failed — the file could not be stored.')->error();

            return redirect()->route('admin.service-management.tickets.show', $ticket);
        }

        $ticket->media()->create([
            'media_type'        => ServiceMediaType::fromMime($uploaded['mime_type'], $uploaded['file_extension']),
            'category'          => $category->value,
            'workflow_stage'    => $stage->value,
            'retention_class'   => $stage->defaultRetentionClass()->value,
            'disk'              => 'service_media',
            'file_path'         => $uploaded['file_path'],
            'original_filename' => $uploaded['original_filename'],
            'mime_type'         => $uploaded['mime_type'],
            'file_size'         => $uploaded['file_size'],
            'uploaded_by'       => auth()->id(),
            'notes'             => $request->validated('notes'),
        ]);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('File uploaded to ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

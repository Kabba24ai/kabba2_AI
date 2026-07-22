<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceMediaWorkflowStage;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveTicketRequest;
use App\Services\ServiceManagement\ServiceTicketIntakeService;

class StoreController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke(SaveTicketRequest $request)
    {
        $validated = $request->validated();

        // ST-1: creation, defaults, override event, complaint snapshots,
        // personnel, and completion-timestamp stamping are all owned by the
        // canonical intake service. This controller only assembles the
        // web-form attributes and handles the HTTP-only evidence upload.
        $attributes = array_merge(
            collect($validated)->except([
                'personnel', 'team_leader_id', 'intake', 'order_id', 'rental_date',
                'equipment_override_id', 'equipment_override_reason',
                'complaints', 'evidence', 'idempotency_token',
            ])->all(),
            $this->orderReferenceFields($validated),
            $this->equipmentOverrideFields($validated),
        );

        $ticket = ServiceTicketIntakeService::create(
            attributes: $attributes,
            complaintSymptomIds: $validated['complaints'] ?? [],
            personnelIds: $validated['personnel'] ?? [],
            teamLeaderId: isset($validated['team_leader_id']) ? (int) $validated['team_leader_id'] : null,
            idempotencyKey: $request->input('idempotency_token'),
        );

        // Evidence upload is HTTP-only and runs after creation. Guarded by
        // wasRecentlyCreated so an idempotent re-submit never re-uploads to
        // an already-created ticket.
        if ($ticket->wasRecentlyCreated) {
            foreach ($request->file('evidence', []) as $file) {
                $uploaded = MediaHelper::uploadServiceMediaFile($file, ServiceMediaWorkflowStage::Complaint, $ticket);
                if (!$uploaded) {
                    continue;
                }

                $ticket->media()->create([
                    'media_type'        => ServiceMediaType::fromMime($uploaded['mime_type'], $uploaded['file_extension']),
                    'category'          => ServiceMediaCategory::ComplaintEvidence->value,
                    'workflow_stage'    => ServiceMediaWorkflowStage::Complaint->value,
                    'retention_class'   => ServiceMediaWorkflowStage::Complaint->defaultRetentionClass()->value,
                    'disk'              => 'service_media',
                    'file_path'         => $uploaded['file_path'],
                    'original_filename' => $uploaded['original_filename'],
                    'mime_type'         => $uploaded['mime_type'],
                    'file_size'         => $uploaded['file_size'],
                    'uploaded_by'       => auth()->id(),
                ]);
            }
        }

        flash('Service ticket ' . $ticket->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

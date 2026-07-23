<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceMediaWorkflowStage;
use App\Enums\Service\ServiceType;
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

        $isStandard = ($validated['ticket_source'] ?? null) === 'standard';

        // Non-column form keys (search aids, order/override inputs, uploads) are
        // stripped here; the real ticket columns are assembled per-path below.
        $baseAttributes = collect($validated)->except([
            'personnel', 'team_leader_id', 'intake', 'ticket_source', 'order_id', 'rental_date',
            'equipment_category_id', 'equipment_override_id', 'equipment_override_reason',
            'complaints', 'evidence', 'idempotency_token',
        ])->all();

        // ST-1: creation, defaults, override event, complaint snapshots,
        // personnel, and completion-timestamp stamping are all owned by the
        // canonical intake service. This controller only assembles the
        // web-form attributes and handles the HTTP-only evidence upload.
        if ($isStandard) {
            // Standard Equipment Ticket — a fleet unit on our lot. Classified as
            // Internal Repair (the canonical existing type), and hard-isolated
            // from any customer/order context regardless of what the client
            // sent (the validator already rejects such payloads; this is
            // defense in depth so a stale/forged field can never persist).
            $attributes = array_merge($baseAttributes, [
                'service_type'              => ServiceType::InternalRepair->value,
                'order_id'                  => null,
                'customer_id'               => null,
                'order_equipment_id'        => null,
                'rental_date'               => null,
                'equipment_override'        => false,
                'equipment_override_reason' => null,
                'equipment_override_by'     => null,
                'equipment_override_at'     => null,
            ]);
        } else {
            $attributes = array_merge(
                $baseAttributes,
                $this->orderReferenceFields($validated),
                $this->equipmentOverrideFields($validated),
            );
        }

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

<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServiceType;
use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceTicketEventType;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveTicketRequest;
use App\Models\Service\ServiceComplaintType;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketEvent;

class StoreController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke(SaveTicketRequest $request)
    {
        $validated = $request->validated();

        $ticket = ServiceTicket::create(array_merge(
            collect($validated)->except([
                'personnel', 'team_leader_id', 'intake', 'order_id', 'rental_date',
                'equipment_override_id', 'equipment_override_reason',
                'complaints', 'evidence',
            ])->all(),
            $this->orderReferenceFields($validated),
            $this->equipmentOverrideFields($validated),
            [
                // Diagnostic-first defaults: intake starts Open with an
                // undecided responsibility; diagnosis determines the path.
                // The rental-order intake form omits type/location/opened —
                // the safest existing values apply.
                'service_type'             => $validated['service_type'] ?? ServiceType::CustomerDamageRepair->value,
                'service_location'         => $validated['service_location'] ?? ServiceLocation::InShop->value,
                'opened_at'                => $validated['opened_at'] ?? now()->format('Y-m-d'),
                'repair_status'            => $validated['repair_status'] ?? RepairStatus::Open->value,
                'financial_responsibility' => $validated['financial_responsibility'] ?? FinancialResponsibility::Pending->value,
                'financial_status'         => $validated['financial_status'] ?? FinancialStatus::NotBillable->value,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ],
        ));

        // Fully traceable override: original unit, corrected unit, who, when, why
        if ($ticket->equipment_override) {
            ServiceTicketEvent::record(
                $ticket->id,
                ServiceTicketEventType::EquipmentOverride,
                $ticket->orderEquipment?->equipment_name . ($ticket->orderEquipment?->equipment_id ? ' (' . $ticket->orderEquipment->equipment_id . ')' : ''),
                $ticket->equipment?->equipment_name . ($ticket->equipment?->equipment_id ? ' (' . $ticket->equipment->equipment_id . ')' : ''),
                $ticket->equipment_override_reason,
            );
        }

        // Structured complaints: one record each, with name/group snapshots
        // so the ticket keeps what was reported even if the library changes.
        $complaintTypes = ServiceComplaintType::whereIn('id', $validated['complaints'] ?? [])->get();
        foreach ($complaintTypes as $type) {
            $ticket->complaints()->create([
                'service_complaint_type_id' => $type->id,
                'name'                      => $type->name,
                'system_group'              => $type->system_group->value,
            ]);
        }

        // Complaint evidence rides the standard ticket-media pipeline
        foreach ($request->file('evidence', []) as $file) {
            $uploaded = MediaHelper::uploadStorageFile('Public Asset', $file, 'service_tickets', $ticket);
            $mediaObj = $uploaded['mediaObj'] ?? null;
            if (!$mediaObj) {
                continue;
            }

            $ticket->media()->create([
                'media_id'          => $mediaObj->id,
                'media_type'        => ServiceMediaType::fromMime($mediaObj->mime_type, $mediaObj->file_extension),
                'category'          => ServiceMediaCategory::ComplaintEvidence->value,
                'file_path'         => $mediaObj->getFilePath(),
                'original_filename' => $mediaObj->original_file_name,
                'mime_type'         => $mediaObj->mime_type,
                'file_size'         => $mediaObj->file_size,
                'uploaded_by'       => auth()->id(),
            ]);
        }

        $ticket->syncPersonnel(
            $validated['personnel'] ?? [],
            isset($validated['team_leader_id']) ? (int) $validated['team_leader_id'] : null,
        );

        // Completed/closed timestamps if the ticket is created directly in
        // one of those states (edge case, but keeps data consistent).
        $ticket->transitionTo($ticket->repair_status, $ticket->blocked_reason, $ticket->expected_action_date);

        flash('Service ticket ' . $ticket->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServiceMediaCategory;
use App\Enums\Service\ServiceMediaType;
use App\Enums\Service\ServiceMediaWorkflowStage;
use App\Enums\Service\ServiceType;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FieldService\SaveFieldTicketRequest;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\Orders\Order;
use App\Models\Service\ServiceSymptom;
use App\Services\ServiceManagement\ServiceProblemLibrary;
use App\Services\ServiceManagement\ServiceTicketIntakeService;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(SaveFieldTicketRequest $request)
    {
        $validated = $request->validated();

        // The Order is canonical — Customer / Contact / Delivery Address all
        // derive from it unless the dispatcher intentionally overrode them.
        $order = Order::with('shippingAddress')->findOrFail($validated['order_id']);
        $ship  = $order->shippingAddress;

        // Contact: the order's, or a deliberately-named "someone else".
        if (($validated['contact_source'] ?? 'order') === 'order') {
            $contactName  = $ship?->full_name ?: $order->customer_name;
            $contactPhone = $ship?->phone ?: $order->customer_phone;
        } else {
            $contactName  = $validated['contact_name'] ?? null;
            $contactPhone = $validated['contact_phone'] ?? null;
        }

        // Service location: the order's delivery address, or a different one.
        if (($validated['location_source'] ?? 'delivery') === 'delivery') {
            $jobSiteAddress = $ship?->full_address;
        } else {
            $jobSiteAddress = trim(implode(', ', array_filter([
                $validated['loc_street'] ?? null,
                $validated['loc_city'] ?? null,
                trim(($validated['loc_state'] ?? '') . ' ' . ($validated['loc_zip'] ?? '')),
            ])));
        }

        // Structured complaints from the shared Problem Library. Defense-in-depth:
        // keep ONLY the symptoms applicable to the selected equipment (same
        // server resolver the request validates against). Complaint Details is
        // the canonical narrative (customer_complaint) — shared with Standard.
        $applicableIds = ServiceProblemLibrary::applicableSymptomIdsForEquipment(
            (int) $validated['equipment_id']
        )->all();
        $complaintIds = array_values(array_intersect(
            array_map('intval', $validated['complaints'] ?? []),
            $applicableIds
        ));
        $libraryNames = $complaintIds
            ? ServiceSymptom::whereIn('id', $complaintIds)->pluck('name')->all()
            : [];
        $complaintDetails = trim((string) ($validated['customer_complaint'] ?? '')) ?: null;

        // Mission summary (backward compatible): canonical complaint names, then
        // Complaint Details.
        $problemSummary = trim(
            implode('; ', $libraryNames)
            . ($complaintDetails ? ($libraryNames ? ' — ' : '') . $complaintDetails : '')
        ) ?: 'See reported problems.';

        // Companion Service Ticket customer_complaint = the canonical Complaint
        // Details (structured complaints ride as complaint records).
        $companionComplaint = $complaintDetails ?: $problemSummary;

        // Mission column values — strip the request-only + companion-only keys.
        $missionAttributes = collect($validated)->except([
            'contact_source', 'location_source', 'loc_street', 'loc_city', 'loc_state', 'loc_zip',
            'complaints', 'customer_complaint', 'contact_name', 'contact_phone', 'evidence',
        ])->all();

        $missionAttributes = array_merge($missionAttributes, [
            'customer_id'      => $order->customer_id,
            'contact_name'     => $contactName,
            'contact_phone'    => $contactPhone,
            'job_site_address' => $jobSiteAddress,
            'reported_at'      => now(),           // server timestamp is the truth
            'problem_summary'  => $problemSummary,
            'created_by'       => auth()->id(),
        ]);

        // Mission + companion canonical service ticket, created atomically.
        [$mission, $serviceTicket] = DB::transaction(function () use ($missionAttributes, $complaintIds, $companionComplaint) {
            $mission = FieldServiceTicket::create($missionAttributes);

            $serviceTicket = ServiceTicketIntakeService::create(
                attributes: [
                    'service_type'       => ServiceType::FieldServiceCall->value,
                    'service_location'   => ServiceLocation::CustomerSite->value,
                    'equipment_id'       => $mission->equipment_id,
                    'order_id'           => $mission->order_id,
                    'customer_id'        => $mission->customer_id,
                    'priority'           => $mission->priority->value,
                    'customer_complaint' => $companionComplaint,
                    'opened_at'          => now(),
                ],
                complaintSymptomIds: $complaintIds,
                personnelIds: $mission->technician_id ? [$mission->technician_id] : [],
                teamLeaderId: $mission->technician_id,
                idempotencyKey: 'field_service_ticket:' . $mission->id,
            );
            $mission->update(['service_ticket_id' => $serviceTicket->id]);

            return [$mission, $serviceTicket];
        });

        // Complaint Evidence → the companion Service Ticket (HTTP-only work, after
        // the transaction; guarded by wasRecentlyCreated so an idempotent re-submit
        // never re-uploads). Same media architecture as Standard Service.
        if ($serviceTicket->wasRecentlyCreated) {
            foreach ($request->file('evidence', []) as $file) {
                $uploaded = MediaHelper::uploadServiceMediaFile($file, ServiceMediaWorkflowStage::Complaint, $serviceTicket);
                if (!$uploaded) {
                    continue;
                }
                $serviceTicket->media()->create([
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

        flash('Field service request ' . $mission->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.field-service.tickets.show', $mission);
    }
}

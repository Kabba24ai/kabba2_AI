<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServiceType;
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

        // Reported problems: library selections become structured complaints on
        // the companion; free-text "Other" problems and additional details are
        // captured as text (no forced, inaccurate library match).
        // Defense-in-depth: build the companion complaint ids from ONLY the
        // symptoms applicable to the selected equipment (same server-side
        // resolver the request validates against), so nothing non-applicable
        // can ever persist even if validation is bypassed.
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
        $customProblems = array_values(array_filter(
            array_map('trim', $validated['custom_problems'] ?? []),
            fn ($s) => $s !== ''
        ));
        $additional = $validated['customer_complaint'] ?? null;

        // Mission summary: every stated problem (library + Other), then details.
        $allProblemNames = array_merge($libraryNames, $customProblems);
        $problemSummary  = trim(
            implode('; ', $allProblemNames)
            . ($additional ? ($allProblemNames ? ' — ' : '') . $additional : '')
        ) ?: 'See reported problems.';

        // Companion customer complaint: the Other problems + free-text detail
        // (library problems ride as structured complaint records).
        $companionComplaint = trim(
            implode('; ', $customProblems)
            . ($additional ? ($customProblems ? ' — ' : '') . $additional : '')
        ) ?: $problemSummary;

        // Mission column values — strip the request-only routing keys, then
        // merge the resolved/derived values.
        $missionAttributes = collect($validated)->except([
            'contact_source', 'location_source', 'loc_street', 'loc_city', 'loc_state', 'loc_zip',
            'complaints', 'customer_complaint', 'contact_name', 'contact_phone',
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

        foreach (['photos_received', 'video_received', 'media_reviewed', 'additional_media_required', 'media_bypassed'] as $flag) {
            $missionAttributes[$flag] = $request->boolean($flag);
        }

        // Mission + companion canonical service ticket, created atomically. The
        // companion carries the SAME structured problems (shared vocabulary) and
        // the free-text detail as its customer complaint.
        $ticket = DB::transaction(function () use ($missionAttributes, $complaintIds, $companionComplaint) {
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

            return $mission;
        });

        flash('Field service request ' . $ticket->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}

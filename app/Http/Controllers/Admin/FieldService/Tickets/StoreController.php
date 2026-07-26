<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FieldService\SaveFieldTicketRequest;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\Orders\Order;
use App\Services\ServiceManagement\ServiceTicketIntakeService;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(SaveFieldTicketRequest $request)
    {
        $validated = $request->validated();

        // The Order stays the source of truth — customer is derived from
        // the selected order, never entered separately.
        $validated['customer_id'] = !empty($validated['order_id'])
            ? Order::find($validated['order_id'])?->customer_id
            : null;

        foreach (['photos_received', 'video_received', 'media_reviewed', 'additional_media_required', 'media_bypassed'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        // The mission and its companion canonical service ticket are created
        // atomically — the two can never diverge (an orphaned mission with no
        // board card, or a companion with no mission).
        $ticket = DB::transaction(function () use ($validated) {
            $mission = FieldServiceTicket::create($validated + [
                'created_by' => auth()->id(),
            ]);

            // Consolidation: every field mission gets a companion service ticket
            // so it lands on the Operations Board like all other service work.
            // The mission (dispatch/route/on-site) stays here; the companion
            // carries board presence, assignment, and lifecycle. Keyed on the
            // mission id, so re-running this creation for the SAME mission
            // returns the existing ticket — exactly one canonical ticket per
            // mission, never a duplicate.
            $serviceTicket = ServiceTicketIntakeService::create(
                attributes: [
                    'service_type'       => ServiceType::FieldServiceCall->value,
                    'service_location'   => ServiceLocation::CustomerSite->value,
                    'equipment_id'       => $mission->equipment_id,
                    'order_id'           => $mission->order_id,
                    'customer_id'        => $mission->customer_id,
                    'priority'           => $mission->priority->value,
                    'customer_complaint' => $mission->problem_summary,
                    'opened_at'          => now(),
                ],
                personnelIds: $mission->technician_id ? [$mission->technician_id] : [],
                teamLeaderId: $mission->technician_id,
                idempotencyKey: 'field_service_ticket:' . $mission->id,
            );
            $mission->update(['service_ticket_id' => $serviceTicket->id]);

            return $mission;
        });

        flash('Field service ticket ' . $ticket->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}

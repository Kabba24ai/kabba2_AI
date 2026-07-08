<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveTicketRequest;
use App\Models\Service\ServiceTicket;

class StoreController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke(SaveTicketRequest $request)
    {
        $validated = $request->validated();

        $ticket = ServiceTicket::create(array_merge(
            collect($validated)->except(['personnel', 'team_leader_id', 'intake', 'order_id', 'rental_date'])->all(),
            $this->orderReferenceFields($validated),
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

<?php

namespace App\Http\Controllers\Admin\ServiceManagement\CustomerDamage;

use App\Http\Controllers\Controller;
use App\Models\Service\CustomerDamageStaging;
use App\Services\Service\CustomerDamageStagingService;
use Illuminate\Http\Request;

/**
 * Customer Damage disposition — the only writer of staging workflow state.
 * Downstream records are created exclusively through the canonical paths
 * inside CustomerDamageStagingService (ChargeService::createManualCharge,
 * ServiceTicketIntakeService::create); this controller validates, dispatches,
 * and translates service guards into safe HTTP responses.
 */
class DispositionController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $validated = $request->validate([
            'action'         => ['required', 'in:in_review,no_action,charge_customer,service_ticket'],
            'note'           => ['nullable', 'string', 'max:2000', 'required_if:action,no_action'],
            'amount'         => ['nullable', 'numeric', 'min:0.01'],
            'sales_tax_type' => ['nullable', 'in:add,free,reverse'],
        ]);

        $staging = CustomerDamageStaging::where('unique_id', $uniqueId)->firstOrFail();
        $userId = (int) auth()->id();

        try {
            switch ($validated['action']) {
                case 'in_review':
                    CustomerDamageStagingService::markInReview($staging, $userId, $validated['note'] ?? null);

                    return response()->json(['success' => true, 'message' => 'Marked as In Review.']);

                case 'no_action':
                    CustomerDamageStagingService::disposeNoAction($staging, $userId, $validated['note']);

                    return response()->json(['success' => true, 'message' => 'Closed — no action required.']);

                case 'charge_customer':
                    $row = CustomerDamageStagingService::disposeChargeCustomer(
                        $staging,
                        $userId,
                        isset($validated['amount']) ? (float) $validated['amount'] : null,
                        $validated['sales_tax_type'] ?? null,
                        $validated['note'] ?? null,
                    );

                    return response()->json([
                        'success' => true,
                        'message' => $row->billing_charge_id
                            ? 'Damage charge linked — manage it through the Billing Engine.'
                            : 'Disposition recorded.',
                    ]);

                case 'service_ticket':
                    $ticket = CustomerDamageStagingService::disposeServiceTicket($staging, $userId, $validated['note'] ?? null);

                    return response()->json([
                        'success' => true,
                        'message' => "Service ticket {$ticket->ticket_number} linked.",
                        'ticket_url' => route('admin.service-management.tickets.show', $ticket),
                    ]);
            }
        } catch (\RuntimeException $e) {
            // Service guards (stale record, missing amount/equipment,
            // concurrent processing) — safe, user-facing messages.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}

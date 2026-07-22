<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Settlement;

use App\Enums\Service\FinancialStatus;
use App\Enums\Service\ServiceTicketEventType;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketEvent;
use App\Models\Service\ServiceTicketSettlement;
use App\Services\ChargeService;
use App\Services\ServiceManagement\SettlementPackage;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Create Customer Charge: persist the Settlement Package and hand it to
     * the canonical Billing Engine (ST-2a) as a real ServiceTicket
     * BillingCharge + CustomerAccount ledger row — idempotent, refundable,
     * payable through the shared payment surfaces. No payment is processed
     * here. Totals come exclusively from SettlementPackage; any amounts in
     * the request are ignored. Tax-free per the approved decision.
     */
    public function __invoke(ServiceTicket $ticket)
    {
        if (!$ticket->canCreateCustomerCharge()) {
            flash('Customer charge cannot be created: ' . $ticket->chargeCreationBlockers()->implode('; ') . '.')->error();

            return redirect()->route('admin.service-management.tickets.settlement.preview', $ticket);
        }

        $package = SettlementPackage::fromTicket($ticket);

        if ($package->finalAmount() <= 0) {
            flash('Nothing to charge — the settlement amount is $0.00 after credits.')->error();

            return redirect()->route('admin.service-management.tickets.settlement.preview', $ticket);
        }

        DB::transaction(function () use ($ticket, $package) {
            $settlement = ServiceTicketSettlement::create([
                'service_ticket_id' => $ticket->id,
                'order_id'          => $ticket->order_id,
                'customer_id'       => $ticket->customer_id,
                'equipment_id'      => $ticket->equipment_id,
                'status'            => ServiceTicketSettlement::STATUS_CREATED,
                'labor_total'       => $package->laborTotal(),
                'parts_total'       => $package->partsTotal(),
                'other_total'       => $package->otherTotal(),
                'subtotal'          => $package->subtotal(),
                'credits_total'     => $package->creditsTotal(),
                'final_amount'      => $package->finalAmount(),
                'package'           => $package->toArray(),
                'created_by'        => auth()->id(),
            ]);

            // Canonical Billing Engine handoff (ST-2a): a real ServiceTicket
            // BillingCharge + CustomerAccount ledger row, tax-free,
            // idempotency-keyed on the settlement. Runs inside this
            // transaction — a bridge failure rolls the whole settlement back.
            $charge = ChargeService::createServiceCharge(
                customerId: (int) $ticket->customer_id,
                amount: $package->finalAmount(),
                orderId: $ticket->order_id,
                responsibleUserId: (int) auth()->id(),
                notes: 'Service repair settlement — ' . $ticket->ticket_number,
                idempotencyKey: 'service_settlement:' . $settlement->id,
                sourceReferenceType: 'ServiceTicketSettlement',
                sourceReferenceId: $settlement->id,
            );

            $settlement->update(['billing_charge_id' => $charge->id]);

            // Stamp the consumed lines so they can never be billed twice.
            $ticket->chargeLines()->where('billable', true)->whereNull('source_type')->update([
                'source_type' => 'service_ticket_settlement',
                'source_id'   => $settlement->id,
            ]);
            $ticket->partsUsed()->where('billable', true)->whereNull('source_type')->update([
                'source_type' => 'service_ticket_settlement',
                'source_id'   => $settlement->id,
            ]);

            // Apply the credits that were consumed by this settlement.
            $ticket->fill([
                'diagnostic_fee_credited' => $package->credits()->contains('key', 'diagnostic_fee')
                    ? true : $ticket->diagnostic_fee_credited,
                'parts_deposit_applied_to_final_invoice' => $package->credits()->contains('key', 'parts_deposit')
                    ? true : $ticket->parts_deposit_applied_to_final_invoice,
                'financial_status' => FinancialStatus::ChargeCreated,
                'updated_by'       => auth()->id(),
            ])->save();

            ServiceTicketEvent::record(
                $ticket->id,
                ServiceTicketEventType::CustomerChargeCreated,
                notes: sprintf(
                    'Customer charge of $%s created on order (billing charge %s)',
                    number_format($package->finalAmount(), 2),
                    $charge->unique_id,
                ),
                metadata: ['settlement_id' => $settlement->id, 'billing_charge_id' => $charge->id],
            );
        });

        flash('Customer charge created for ' . $ticket->ticket_number . '. Billing and payment are now handled on the order.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

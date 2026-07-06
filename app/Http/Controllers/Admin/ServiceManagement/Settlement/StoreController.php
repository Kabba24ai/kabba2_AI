<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Settlement;

use App\Enums\Service\FinancialStatus;
use App\Enums\Service\ServiceTicketEventType;
use App\Http\Controllers\Controller;
use App\Models\Orders\OrderExtraCharges;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketEvent;
use App\Models\Service\ServiceTicketSettlement;
use App\Services\ServiceManagement\SettlementPackage;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Create Customer Charge: persist the Settlement Package and hand it to
     * the Financial Engine as an Order Extra Charge. The Service Module's
     * responsibility ends here — no payment is processed, no payment records
     * are created. Totals come exclusively from SettlementPackage; any
     * amounts in the request are ignored.
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

            // Handoff to the Financial Engine via Order Extra Payments.
            // payment_type stays null — payment collection is not our job.
            $charge = OrderExtraCharges::create([
                'order_id'    => $ticket->order_id,
                'customer_id' => $ticket->customer_id,
                'type'        => 'service',
                'amount'      => $package->finalAmount(),
                'notes'       => 'Service repair settlement — ' . $ticket->ticket_number,
            ]);

            // source_type/source_id are new columns not in the OEP model's
            // fillable list (we don't modify Billing Engine code) — set them
            // directly for drill-down and duplicate protection.
            $charge->forceFill([
                'source_type' => 'service_ticket_settlement',
                'source_id'   => $settlement->id,
            ])->save();

            $settlement->update(['order_extra_charge_id' => $charge->id]);

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
                    'Customer charge of $%s created on order (extra charge %s)',
                    number_format($package->finalAmount(), 2),
                    $charge->unique_id,
                ),
                metadata: ['settlement_id' => $settlement->id, 'order_extra_charge_id' => $charge->id],
            );
        });

        flash('Customer charge created for ' . $ticket->ticket_number . '. Billing and payment are now handled on the order.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

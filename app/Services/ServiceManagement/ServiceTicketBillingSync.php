<?php

namespace App\Services\ServiceManagement;

use App\Enums\Service\FinancialStatus;
use App\Models\Orders\BillingCharge;
use App\Models\Service\ServiceTicketSettlement;

/**
 * ST-2b — reflect payment of a service settlement charge back onto its
 * ticket. Called from the payment flow after a BillingCharge is marked
 * paid; a no-op for any charge that isn't a service settlement charge, so
 * the payment controller stays decoupled from the Service module (one
 * explicit call, Service owns the logic).
 *
 * Service settlement charges are paid in one shot (BillingCharge carries no
 * partial-payment tracking, same as fuel/damage), so a paid charge advances
 * the ticket straight to Paid. The financial_status change is auto-logged by
 * the ServiceTicket model's updated hook.
 */
class ServiceTicketBillingSync
{
    public static function syncPaidCharge(BillingCharge $charge): void
    {
        if ($charge->billing_charge_type?->value !== 'service_ticket') {
            return;
        }

        $settlement = ServiceTicketSettlement::where('billing_charge_id', $charge->id)
            ->with('ticket')
            ->first();

        $ticket = $settlement?->ticket;
        if ($ticket === null) {
            return;
        }

        // Only advance from the charge-created state; never override a
        // written-off / warranty / already-paid status.
        if ($ticket->financial_status === FinancialStatus::ChargeCreated) {
            $ticket->update(['financial_status' => FinancialStatus::Paid]);
        }
    }
}

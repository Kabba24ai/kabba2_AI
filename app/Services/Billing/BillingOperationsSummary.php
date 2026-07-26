<?php

namespace App\Services\Billing;

use App\Enums\Service\ServiceType;
use App\Models\Orders\BillingCharge;
use App\Models\Service\ServiceTicketSettlement;
use App\Services\AlertLifecycleService;
use App\Services\Alerts\ChargeAlertQueue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Canonical READ-ONLY composition of the Task Manager Billing Operations
 * datasets and overview metrics.
 *
 * This is the SINGLE source of the active Fuel and Damage queues that both
 * the Billing Operations Overview and the Damage Charge Resolution workspace
 * consume, so their counts can never drift. It performs query/data
 * composition only — no writes, no request handling, no controller-specific
 * presentation. The workspace controllers still own their own filtering,
 * pagination, terminal-history queries, and rendering.
 *
 * The damage active set deliberately reproduces the workspace's rule exactly:
 * the canonical ChargeAlertQueue::damageAlerts() PLUS customer-responsibility
 * Service Ticket damage charges (billing_charge_type='service_ticket' that are
 * customer-damage liability) — see serviceTicketDamageCharges().
 */
class BillingOperationsSummary
{
    /** The canonical active FUEL queue (unfiltered). */
    public function fuelActiveAlerts(): Collection
    {
        return ChargeAlertQueue::fuelAlerts();
    }

    /**
     * The canonical active DAMAGE queue (unfiltered): dashboard damage alerts
     * PLUS qualifying customer-damage Service Ticket charges. Consumed by BOTH
     * the Overview counts and the Damage workspace so the two never diverge.
     */
    public function damageActiveAlerts(): Collection
    {
        return ChargeAlertQueue::damageAlerts()
            ->concat($this->serviceTicketDamageCharges())
            ->sortByDesc('_sort_ts')
            ->values();
    }

    /**
     * A row has no established (collectible) amount — the "Needs Pricing"
     * state. Mirrors the queue partial's $isUnpriced rule so the Overview
     * count, the workspace filter, and the row badge always agree.
     */
    public static function isUnpriced(array $row): bool
    {
        $owed = $row['amountOwed'] ?? '$0.00';

        if ($owed === 'Pending') {
            return true;
        }

        return (float) str_replace(['$', ','], '', $owed) <= 0;
    }

    /**
     * Overview metrics, every value derived from the canonical datasets above
     * or from proven canonical timestamps.
     *
     * resolved_today uses AlertLifecycleService::completedTodayCount() for
     * fuel + damage — the same today-scoped canonical metric the workspaces
     * show as "Completed Today". KNOWN LIMITATION: it counts OrderProduct /
     * CustomerAccount alert completions only; Service Ticket-originated charge
     * resolutions are not yet represented in that lifecycle log.
     */
    public function metrics(): array
    {
        $fuel   = $this->fuelActiveAlerts();
        $damage = $this->damageActiveAlerts();

        return [
            'fuel_open'               => $fuel->count(),
            'damage_open'             => $damage->count(),
            'needs_pricing'           => $damage->filter(fn ($r) => self::isUnpriced($r))->count(),
            'resolved_today'          => AlertLifecycleService::completedTodayCount('fuel')
                                        + AlertLifecycleService::completedTodayCount('damage'),
            'oldest_outstanding_days' => $this->oldestOutstandingDays($fuel->concat($damage)),
        ];
    }

    /**
     * Age (whole days) of the oldest active item across both queues, on the
     * same _sort_ts basis the workspaces already use for "Average Age".
     * Returns null when nothing is outstanding.
     */
    private function oldestOutstandingDays(Collection $items): ?int
    {
        if ($items->isEmpty()) {
            return null;
        }

        $now = Carbon::now()->timestamp;
        $maxAgeSeconds = (int) $items->max(fn ($r) => max(0, $now - (int) ($r['_sort_ts'] ?? $now)));

        return (int) floor($maxAgeSeconds / 86400);
    }

    /**
     * Open (pending) customer-DAMAGE Service Ticket charges.
     *
     * NARROW eligibility (preserved verbatim from the Damage workspace): the
     * charge is billing_charge_type='service_ticket', status pending, bridged
     * to a ServiceTicketSettlement (which only exists once the ticket passed
     * canCreateCustomerCharge() → financial responsibility is CustomerPay),
     * AND the ticket's service_type is CustomerDamageRepair. Non-damage
     * service-ticket charges are excluded. The canonical BillingCharge type is
     * NOT changed and the Billing Engine is untouched — this only reads the
     * existing settlement → ticket relationships. Each row links back to the
     * Service Ticket as its source of truth.
     */
    private function serviceTicketDamageCharges(): Collection
    {
        $charges = BillingCharge::with(['customer.cards', 'parentOrder'])
            ->where('billing_charge_type', 'service_ticket')
            ->where('status', 'pending')
            ->get();

        if ($charges->isEmpty()) {
            return collect();
        }

        $settlements = ServiceTicketSettlement::with([
            'ticket.customer.cards',
            'ticket.order',
            'ticket.equipment',
        ])
            ->whereIn('billing_charge_id', $charges->pluck('id'))
            ->get()
            ->keyBy('billing_charge_id');

        return $charges->map(function ($charge) use ($settlements) {
            $ticket = $settlements->get($charge->id)?->ticket;

            if (! $ticket || $ticket->service_type !== ServiceType::CustomerDamageRepair) {
                return null;
            }

            $order    = $ticket->order ?? $charge->parentOrder;
            $customer = $charge->customer ?? $ticket->customer;
            $amount   = (float) $charge->amount;

            return [
                'id'                       => 30000 + $charge->id,
                'source'                   => 'service_ticket',
                'action_mode'              => 'charge',
                'origin'                   => 'service',
                'customer'                 => [
                    'id'        => $customer?->id,
                    'full_name' => $customer?->full_name,
                    'cards'     => $customer?->cards?->map(fn ($c) => [
                        'id'    => $c->unique_id,
                        'label' => $c->card_number,
                    ])->values() ?? collect(),
                ],
                'customerName'             => $customer?->full_name ?? '—',
                'orderId'                  => $order?->unique_id,
                'order_number'             => $order?->order_number,
                'order_db_id'              => $order?->id,
                'orderLink'                => $order ? route('admin.order-management.orders.edit', $order->unique_id) : null,
                'amountOwed'               => '$' . number_format($amount, 2),
                'date'                     => optional($charge->created_at)->toDateString(),
                '_sort_ts'                 => $charge->created_at?->timestamp ?? 0,
                'type'                     => 'damage',
                'notes'                    => collect(),
                'equipment'                => ['name' => $ticket->equipment?->equipment_name],
                'order_product'            => null,
                'billing_charge_unique_id' => $charge->unique_id,
                'customer_account_id'      => null,
                'source_type'              => 'Service Ticket',
                'source_link'              => route('admin.service-management.tickets.show', $ticket->id),
            ];
        })->filter()->values();
    }
}

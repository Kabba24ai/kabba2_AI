<?php

namespace App\Services;

use App\Enums\Billing\BillingChargeStatus;
use App\Models\Orders\BillingCharge;

/**
 * Billing Charge Operations Commonization — the ONE presentation vocabulary
 * for billing charges, everywhere they render (Fuel Workspace queue, Order
 * Details Billing Engine table, dashboards, and the upcoming Damage
 * Workspace). Presentation ONLY: status label/styling, origin label,
 * money/age formatting. Payment, refund, tax, and lifecycle business logic
 * live in their existing services and never move here.
 *
 * Domain ownership is respected, not duplicated: BillingChargeStatus (the
 * enum) remains the authority on BillingCharge status labels and colors —
 * this presenter maps the LEGACY alert-status strings (CustomerAccount
 * fuel_alert_status / damage_alert_status, OrderProduct charge statuses)
 * onto that same canonical palette so both worlds present identically.
 *
 * Canonical status palette (approved):
 *   Pending/Active  → orange   Paid/Completed → green   Resolved → blue
 *   Uncollectible   → gray     Voided         → gray + strikethrough
 */
class BillingChargePresenter
{
    /**
     * Canonical status badge for either world:
     *   - BillingChargeStatus enum (billing_charges rows)
     *   - legacy alert-status string: 'pending'|'completed'|'resolved'|
     *     'uncollectible'|null (null/unknown = still outstanding)
     *
     * @return array{label: string, classes: string}
     */
    public static function statusBadge(BillingChargeStatus|string|null $status): array
    {
        if ($status instanceof BillingChargeStatus) {
            return ['label' => $status->label(), 'classes' => $status->badgeClass()];
        }

        return match ($status) {
            // Alert-world 'completed' means the money was collected — same
            // state Paid represents on the BillingCharge side, same green.
            'completed'     => ['label' => 'Completed', 'classes' => BillingChargeStatus::Paid->badgeClass()],
            'resolved'      => ['label' => BillingChargeStatus::Resolved->label(), 'classes' => BillingChargeStatus::Resolved->badgeClass()],
            'uncollectible' => ['label' => BillingChargeStatus::Uncollectible->label(), 'classes' => BillingChargeStatus::Uncollectible->badgeClass()],
            default         => ['label' => BillingChargeStatus::Pending->label(), 'classes' => BillingChargeStatus::Pending->badgeClass()],
        };
    }

    /**
     * Origin is informational ONLY — it never gates capability (approved
     * architecture rule). Labels are asserted only from data that reliably
     * proves them:
     *   - order_product_id present    → created by the Customer Checklist
     *   - metadata.source_context     → which admin surface created it
     *     (recorded by ChargeService::createManualCharge since Phase 1)
     *   - otherwise                   → provably manual, surface unknown
     * Extension charges return null — their type column already names them.
     *
     * @return array{label: string, title: string}|null
     */
    public static function originForCharge(BillingCharge $charge): ?array
    {
        $type = $charge->billing_charge_type?->value;

        if ($type !== 'fuel' && $type !== 'damage') {
            return null;
        }

        if ($charge->order_product_id !== null) {
            return ['label' => 'Checklist', 'title' => 'Created automatically by the customer return checklist'];
        }

        $context = $charge->metadata['source_context'] ?? null;

        $surface = match ($context) {
            'dashboard'       => 'Dashboard',
            'fuel_workspace'  => 'Fuel Workspace',
            'crm'             => 'CRM',
            'order_details'   => 'Order Details',
            default           => null,
        };

        return [
            'label' => 'Manual',
            'title' => $surface
                ? "Created manually from {$surface}"
                : 'Created manually',
        ];
    }

    /** Canonical money formatting for charge amounts. */
    public static function money(float|int|null $amount): string
    {
        return '$' . number_format((float) ($amount ?? 0), 2);
    }

    /** Whole-day age from a unix timestamp; null when unknown. */
    public static function ageDays(?int $timestamp): ?int
    {
        if (!$timestamp || $timestamp <= 0) {
            return null;
        }

        return max(0, (int) floor((now()->timestamp - $timestamp) / 86400));
    }
}

<?php

namespace App\Services\Billing;

use App\Models\Service\CustomerDamageStaging;
use Illuminate\Support\Collection;

/**
 * Resolves the authoritative SOURCE of a damage-charge workspace row for the
 * "Source / View Source" affordance — using canonical relationships only.
 *
 * The damage workspace is a billing-resolution surface, NOT the source of
 * truth for the damage. Each row links back to where the damage actually
 * lives:
 *   - Service Ticket  — when the CustomerDamageStaging bridge carries a
 *                       service_ticket_id (damage dispositioned to service).
 *   - Customer Checklist — return-checklist / equipment-damage origin (there
 *                       is no dedicated admin checklist viewer, so the link
 *                       targets the Order, where the checklist damage lives).
 *   - Manual          — a manually-created damage charge (CRM / dashboard).
 *
 * When no canonical source can be proven, the row is left with a null source
 * and the view simply omits the Source line — the linkage is never inferred
 * or fabricated.
 */
class DamageChargeSourceResolver
{
    /**
     * Enrich each damage alert row with 'source_type' + 'source_link'.
     * Staging rows are batch-loaded once (keyed by order_product_id) to avoid
     * an N+1 across the queue.
     */
    public static function enrich(Collection $rows): Collection
    {
        $opIds = $rows
            ->map(fn ($r) => $r['order_product']['id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $stagingByOp = $opIds->isEmpty()
            ? collect()
            : CustomerDamageStaging::whereIn('order_product_id', $opIds)
                ->get()
                ->keyBy('order_product_id');

        return $rows->map(function ($row) use ($stagingByOp) {
            // Rows that already carry a canonical source (e.g. Service Ticket
            // charges resolved by the controller) are left untouched.
            if (! empty($row['source_type'])) {
                return $row;
            }

            [$type, $link] = self::resolveOne($row, $stagingByOp);
            $row['source_type'] = $type;
            $row['source_link'] = $link;

            return $row;
        })->values();
    }

    /**
     * @return array{0: ?string, 1: ?string} [source_type, source_link]
     */
    private static function resolveOne(array $row, Collection $stagingByOp): array
    {
        $isCrm = ($row['source'] ?? null) === 'crm';
        $opId  = $row['order_product']['id'] ?? null;
        $staging = $opId ? $stagingByOp->get($opId) : null;

        // A staging bridge with a ticket linkage means the damage was
        // dispositioned into a Service Ticket — link straight to the ticket.
        if ($staging && $staging->service_ticket_id) {
            return ['Service Ticket', route('admin.service-management.tickets.show', $staging->service_ticket_id)];
        }

        // Return-checklist / equipment-damage origin. No dedicated checklist
        // viewer exists, so the authoritative destination is the Order.
        if (!$isCrm && $opId) {
            return ['Customer Checklist', $row['orderLink'] ?? null];
        }

        // Manually-created damage charge (CRM page or dashboard modal).
        if ($isCrm) {
            return ['Manual', $row['orderLink'] ?? ($row['crmLink'] ?? null)];
        }

        // No canonical source could be proven — handled honestly (no link).
        return [null, null];
    }
}

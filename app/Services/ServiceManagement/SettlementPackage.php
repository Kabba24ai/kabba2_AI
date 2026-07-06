<?php

namespace App\Services\ServiceManagement;

use App\Enums\Service\ServiceChargeType;
use App\Models\Service\ServiceTicket;
use Illuminate\Support\Collection;

/**
 * The formal handoff record between the Service Module and Kabba's
 * centralized Financial Engine.
 *
 * The Service Module PREPARES charges; the Financial Engine OWNS billing,
 * payment processing, refunds, and receivables. This package is the
 * interface between the two: every total is calculated from the underlying
 * billable lines — nothing here is user-editable, and nothing here touches
 * payment processing.
 *
 * Bucketing rule (each billable item lands in exactly one bucket):
 *  - Labor  = billable labor entries + billable charge lines of type Labor
 *  - Parts  = billable parts (customer price) + billable charge lines of type Parts
 *  - Other  = billable charge lines of every remaining type
 *  - Credits = diagnostic fee (paid + creditable + not yet credited)
 *            + parts deposit (paid + creditable + not yet applied)
 */
class SettlementPackage
{
    private function __construct(private ServiceTicket $ticket)
    {
    }

    public static function fromTicket(ServiceTicket $ticket): self
    {
        $ticket->loadMissing(['laborEntries.employee', 'chargeLines', 'partsUsed']);

        return new self($ticket);
    }

    // ── Line buckets ───────────────────────────────────────────────

    public function laborLines(): Collection
    {
        $entries = $this->ticket->laborEntries
            ->where('billable', true)
            ->map(fn ($e) => [
                'source_type' => 'service_ticket_labor_entry',
                'source_id'   => $e->id,
                'description' => trim(($e->employee?->full_name ?? 'Labor') . ($e->labor_description ? ' — ' . $e->labor_description : '')),
                'hours'       => (float) $e->hours,
                'rate'        => $e->labor_rate !== null ? (float) $e->labor_rate : null,
                'total'       => (float) ($e->labor_total ?? 0),
            ]);

        return $entries->concat($this->chargeLinesOfTypes([ServiceChargeType::Labor]))->values();
    }

    public function partsLines(): Collection
    {
        $parts = $this->ticket->partsUsed
            ->where('billable', true)
            ->map(fn ($p) => [
                'source_type'    => 'service_ticket_part',
                'source_id'      => $p->id,
                'part_number'    => $p->part_number,
                'description'    => $p->description,
                'quantity'       => (float) $p->quantity,
                'customer_price' => $p->customer_price !== null ? (float) $p->customer_price : null,
                'total'          => (float) ($p->customer_total ?? 0),
            ]);

        return $parts->concat($this->chargeLinesOfTypes([ServiceChargeType::Parts]))->values();
    }

    public function otherLines(): Collection
    {
        $otherTypes = array_filter(
            ServiceChargeType::cases(),
            fn ($t) => !in_array($t, [ServiceChargeType::Labor, ServiceChargeType::Parts], true)
        );

        return $this->chargeLinesOfTypes($otherTypes)->values();
    }

    private function chargeLinesOfTypes(array $types): Collection
    {
        return $this->ticket->chargeLines
            ->where('billable', true)
            ->whereIn('charge_type', $types)
            ->map(fn ($l) => [
                'source_type' => 'service_ticket_charge_line',
                'source_id'   => $l->id,
                'charge_type' => $l->charge_type->value,
                'description' => $l->charge_type->label() . ' — ' . $l->description,
                'quantity'    => (float) $l->quantity,
                'unit_amount' => (float) $l->unit_amount,
                'total'       => (float) $l->line_total,
            ]);
    }

    /** Credits reduce the amount to create — shown and handed off separately. */
    public function credits(): Collection
    {
        $credits = collect();
        $t = $this->ticket;

        if ($t->diagnostic_fee_required && $t->diagnostic_fee_paid && $t->diagnostic_fee_creditable
            && !$t->diagnostic_fee_credited && $t->diagnostic_fee_amount !== null) {
            $credits->push([
                'key'    => 'diagnostic_fee',
                'label'  => 'Diagnostic Fee Credit',
                'amount' => (float) $t->diagnostic_fee_amount,
            ]);
        }

        if ($t->parts_deposit_required && $t->parts_deposit_paid && $t->parts_deposit_creditable
            && !$t->parts_deposit_applied_to_final_invoice && $t->parts_deposit_amount !== null) {
            $credits->push([
                'key'    => 'parts_deposit',
                'label'  => 'Parts Deposit Credit',
                'amount' => (float) $t->parts_deposit_amount,
            ]);
        }

        return $credits;
    }

    // ── Calculated totals — never user-edited ──────────────────────

    public function laborTotal(): float
    {
        return round($this->laborLines()->sum('total'), 2);
    }

    public function partsTotal(): float
    {
        return round($this->partsLines()->sum('total'), 2);
    }

    public function otherTotal(): float
    {
        return round($this->otherLines()->sum('total'), 2);
    }

    public function subtotal(): float
    {
        return round($this->laborTotal() + $this->partsTotal() + $this->otherTotal(), 2);
    }

    public function creditsTotal(): float
    {
        return round($this->credits()->sum('amount'), 2);
    }

    /** The amount handed to the Financial Engine — never below zero. */
    public function finalAmount(): float
    {
        return max(round($this->subtotal() - $this->creditsTotal(), 2), 0.0);
    }

    /** The full handoff payload persisted on the settlement record. */
    public function toArray(): array
    {
        return [
            'service_ticket_id'     => $this->ticket->id,
            'ticket_number'         => $this->ticket->ticket_number,
            'order_id'              => $this->ticket->order_id,
            'customer_id'           => $this->ticket->customer_id,
            'equipment_id'          => $this->ticket->equipment_id,
            'labor_lines'           => $this->laborLines()->all(),
            'parts_lines'           => $this->partsLines()->all(),
            'other_lines'           => $this->otherLines()->all(),
            'credits'               => $this->credits()->all(),
            'labor_total'           => $this->laborTotal(),
            'parts_total'           => $this->partsTotal(),
            'other_total'           => $this->otherTotal(),
            'subtotal'              => $this->subtotal(),
            'credits_total'         => $this->creditsTotal(),
            'final_amount'          => $this->finalAmount(),
            'created_by'            => auth()->id(),
            'created_at'            => now()->toIso8601String(),
        ];
    }
}

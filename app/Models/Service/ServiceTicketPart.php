<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceTicketEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A part recorded against a service ticket. Free-form — the part does not
 * need to exist in the parts module, and recording one never deducts
 * inventory. Conversion into charge lines is a later phase (source_type /
 * source_id will carry the link to prevent double billing).
 */
class ServiceTicketPart extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_ticket_id',
        'part_number',
        'description',
        'quantity',
        'unit_cost',
        'customer_price',
        'warranty_eligible',
        'billable',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'unit_cost'         => 'decimal:2',
        'customer_price'    => 'decimal:2',
        'warranty_eligible' => 'boolean',
        'billable'          => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $part) {
            $part->quantity ??= 1;
        });

        static::created(function (self $part) {
            ServiceTicketEvent::record(
                $part->service_ticket_id,
                ServiceTicketEventType::PartAdded,
                notes: sprintf('Part added: %s — Qty %s', $part->description, $part->quantityLabel()),
            );
        });

        static::deleted(function (self $part) {
            ServiceTicketEvent::record(
                $part->service_ticket_id,
                ServiceTicketEventType::PartRemoved,
                notes: sprintf('Part removed: %s — Qty %s', $part->description, $part->quantityLabel()),
            );
        });
    }

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    // ── Line math (never re-derive in Blade) ──────────────────────

    /** quantity × unit_cost — what the part costs the company. */
    public function getCostTotalAttribute(): ?float
    {
        return $this->unit_cost !== null
            ? round((float) $this->quantity * (float) $this->unit_cost, 2)
            : null;
    }

    /** quantity × customer_price — what the customer would be charged. */
    public function getCustomerTotalAttribute(): ?float
    {
        return $this->customer_price !== null
            ? round((float) $this->quantity * (float) $this->customer_price, 2)
            : null;
    }

    public function quantityLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->quantity, 2), '0'), '.');
    }
}

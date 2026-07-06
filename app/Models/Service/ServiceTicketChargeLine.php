<?php

namespace App\Models\Service;

use App\Enums\Service\ServiceChargeType;
use App\Enums\Service\ServiceTicketEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A proposed/approved charge prepared on a service ticket. These lines are
 * billing preparation only — converting them into Order Extra Payments,
 * warranty reimbursements, or internal cost postings is a later phase.
 */
class ServiceTicketChargeLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_ticket_id',
        'charge_type',
        'description',
        'quantity',
        'unit_amount',
        'line_total',
        'taxable',
        'billable',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'charge_type' => ServiceChargeType::class,
        'quantity'    => 'decimal:2',
        'unit_amount' => 'decimal:2',
        'line_total'  => 'decimal:2',
        'taxable'     => 'boolean',
        'billable'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $line) {
            $line->quantity ??= 1;
            $line->line_total = round((float) $line->quantity * (float) $line->unit_amount, 2);
        });

        static::created(function (self $line) {
            ServiceTicketEvent::record(
                $line->service_ticket_id,
                ServiceTicketEventType::ChargeLineAdded,
                notes: sprintf('Charge line added: %s — %s ($%s)', $line->charge_type->label(), $line->description, number_format((float) $line->line_total, 2)),
            );
        });

        static::deleted(function (self $line) {
            ServiceTicketEvent::record(
                $line->service_ticket_id,
                ServiceTicketEventType::ChargeLineRemoved,
                notes: sprintf('Charge line removed: %s — %s ($%s)', $line->charge_type->label(), $line->description, number_format((float) $line->line_total, 2)),
            );
        });
    }

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }
}

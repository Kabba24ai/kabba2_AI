<?php

namespace App\Models\Service;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderExtraCharges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The persisted Settlement Package — the handoff record from the Service
 * Module to the Financial Engine. One active settlement per ticket; the
 * Service Module's responsibility ends when this row and its Order Extra
 * Charge exist. Payment status lives with the Financial Engine, never here.
 */
class ServiceTicketSettlement extends Model
{
    use SoftDeletes;

    public const STATUS_CREATED  = 'created';
    public const STATUS_REVERSED = 'reversed'; // future authorized reopen workflow

    protected $fillable = [
        'service_ticket_id',
        'order_id',
        'customer_id',
        'equipment_id',
        'order_extra_charge_id',
        'billing_charge_id',
        'status',
        'labor_total',
        'parts_total',
        'other_total',
        'subtotal',
        'credits_total',
        'final_amount',
        'package',
        'created_by',
    ];

    protected $casts = [
        'labor_total'   => 'decimal:2',
        'parts_total'   => 'decimal:2',
        'other_total'   => 'decimal:2',
        'subtotal'      => 'decimal:2',
        'credits_total' => 'decimal:2',
        'final_amount'  => 'decimal:2',
        'package'       => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function extraCharge()
    {
        return $this->belongsTo(OrderExtraCharges::class, 'order_extra_charge_id');
    }

    /** ST-2a: the canonical Billing Engine charge for this settlement. */
    public function billingCharge()
    {
        return $this->belongsTo(\App\Models\Orders\BillingCharge::class, 'billing_charge_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

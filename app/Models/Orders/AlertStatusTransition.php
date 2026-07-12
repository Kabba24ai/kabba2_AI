<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;

/**
 * Observational record of a Fuel/Damage alert leaving the active queue
 * (Dashboard V2 Phase 1B). Written in the same transaction as the underlying
 * status change; never gates a workflow. See App\Services\AlertLifecycleService.
 */
class AlertStatusTransition extends Model
{
    protected $fillable = [
        'alert_type',
        'source_type',
        'source_id',
        'order_id',
        'previous_status',
        'new_status',
        'idempotency_key',
        'transitioned_at',
        'transitioned_by',
        'service_ticket_id',
    ];

    protected $casts = [
        'transitioned_at' => 'datetime',
    ];
}

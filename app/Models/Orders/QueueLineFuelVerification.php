<?php

namespace App\Models\Orders;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only Queue Fuel Verification event (verified / reversed).
 * Written ONLY by QueueFuelVerificationService; never mutated or deleted.
 * Current state is derived, episode-bound (equipment_soft_assign_id).
 */
class QueueLineFuelVerification extends Model
{
    protected $table = 'queue_line_fuel_verifications';

    public const ACTION_VERIFIED = 'verified';

    public const ACTION_REVERSED = 'reversed';

    public const SOURCE_WEB = 'queue_line_web';

    public const SOURCE_WALL = 'queue_line_wall';

    public const SOURCE_MOBILE = 'queue_line_mobile';

    protected $fillable = [
        'order_product_id',
        'order_id',
        'equipment_id',
        'equipment_soft_assign_id',
        'action',
        'reversed_verification_id',
        'performed_by',
        'created_by',
        'source',
        'reason',
        'idempotency_token',
    ];

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversed_verification_id');
    }

    public function reversedVerification()
    {
        return $this->belongsTo(self::class, 'reversed_verification_id');
    }
}

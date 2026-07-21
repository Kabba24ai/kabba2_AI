<?php

namespace App\Models\Orders;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only Queue Key Confirmation event (confirmed / reversed) —
 * "the key is physically with the staged machine". Written ONLY by
 * QueueLineStagingService; never mutated or deleted. Current state is
 * derived, episode-bound (equipment_soft_assign_id) — the exact sibling of
 * QueueLineFuelVerification.
 */
class QueueLineKeyConfirmation extends Model
{
    protected $table = 'queue_line_key_confirmations';

    public const ACTION_CONFIRMED = 'confirmed';

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
        'reversed_confirmation_id',
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
        return $this->hasOne(self::class, 'reversed_confirmation_id');
    }

    public function reversedConfirmation()
    {
        return $this->belongsTo(self::class, 'reversed_confirmation_id');
    }
}

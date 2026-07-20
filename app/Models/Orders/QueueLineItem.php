<?php

namespace App\Models\Orders;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Queue Line state sidecar row (see queue_line_items migration).
 *
 * One unique row per order_product; created lazily by QueueLineService (the
 * ONLY writer) when a human first acts on the item. Board eligibility is
 * computed — never stored here (QueueLineEligibility owns the predicate).
 */
class QueueLineItem extends Model
{
    use SoftDeletes;

    protected $table = 'queue_line_items';

    protected $fillable = [
        'order_product_id',
        'order_id',
        'staged_at',
        'staged_by',
        'rush_at',
        'rush_by',
        'suppressed_on',
        'suppressed_on_by',
        'suppressed_forever',
        'suppressed_forever_at',
        'suppressed_forever_by',
        'completed_at',
        'completed_via',
        'completed_equipment_id',
    ];

    protected $casts = [
        'staged_at' => 'datetime',
        'rush_at' => 'datetime',
        'suppressed_on' => 'date',
        'suppressed_forever' => 'boolean',
        'suppressed_forever_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function stagedBy()
    {
        return $this->belongsTo(User::class, 'staged_by');
    }

    public function rushBy()
    {
        return $this->belongsTo(User::class, 'rush_by');
    }

    public function isStaged(): bool
    {
        return $this->staged_at !== null;
    }

    public function isRushed(): bool
    {
        return $this->rush_at !== null;
    }

    /** Remove Today applies only while its stored date IS the current operational day. */
    public function isSuppressedToday(): bool
    {
        return $this->suppressed_on !== null
            && $this->suppressed_on->isSameDay(now());
    }
}

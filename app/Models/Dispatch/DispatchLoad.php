<?php

namespace App\Models\Dispatch;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A combined dispatch load — several order_product legs grouped to travel together
 * on one driver/truck. Single-leg ('delivery' or 'return'); membership is via the
 * leg-specific FK on order_products. Planning/visual grouping only: members keep
 * their own priority, checklist, equipment and complete individually.
 */
class DispatchLoad extends Model
{
    protected $fillable = [
        'unique_id',
        'leg',
        'driver_id',
        'label',
        'created_by',
    ];

    public function getRouteKeyName(): string
    {
        return 'unique_id';
    }

    protected static function booted(): void
    {
        static::creating(function (DispatchLoad $load) {
            if (empty($load->unique_id)) {
                $load->unique_id = (string) Str::uuid();
            }
        });
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Order products whose DELIVERY leg belongs to this load. */
    public function deliveryMembers(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'delivery_load_id');
    }

    /** Order products whose RETURN leg belongs to this load. */
    public function pickupMembers(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'pickup_load_id');
    }

    /** The members relation for this load's own leg. */
    public function members(): HasMany
    {
        return $this->leg === 'delivery' ? $this->deliveryMembers() : $this->pickupMembers();
    }

    public function isDelivery(): bool
    {
        return $this->leg === 'delivery';
    }
}

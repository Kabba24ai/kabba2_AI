<?php

namespace App\Models\WaitList;

use App\Enums\WaitList\WaitListRequestType;
use App\Enums\WaitList\WaitListStatus;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One wait list record = one customer need within one equipment category,
 * with one or more selected acceptable PRODUCTS attached (no quantity
 * support by design). The product set is a snapshot owned by the record —
 * later catalog changes never silently alter an existing request. No
 * automatic customer notifications, reservations, holds, or expiration —
 * every contact and disposition decision is manual.
 *
 * Legacy records (request_type category/specific_equipment) that could not
 * be migrated to product selections keep matching through documented
 * fallbacks in WaitListMatcher; their historical rows are never rewritten.
 */
class EquipmentWaitList extends Model
{
    protected $table = 'equipment_wait_lists';

    /** In-memory default matching the DB default. */
    protected $attributes = ['status' => 'active'];

    protected $fillable = [
        'customer_id',
        'customer_name',
        'company_name',
        'phone',
        'email',
        'request_type',
        'product_category_id',
        'store_preference',
        'store_id',
        'reason',
        'internal_notes',
        'status',
        'priority_override',
        'converted_order_id',
        'converted_at',
        'cancelled_by',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'request_type'     => WaitListRequestType::class,
        'store_preference' => WaitListStorePreference::class,
        'status'           => WaitListStatus::class,
        'converted_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /** Legacy specific-equipment unit picks — historical, no longer written. */
    public function items()
    {
        return $this->hasMany(EquipmentWaitListItem::class);
    }

    /** The selected acceptable products for this request (unified workflow). */
    public function selectedProducts()
    {
        return $this->belongsToMany(
            \App\Models\ProductManagement\Product::class,
            'equipment_wait_list_products',
        )->withTimestamps();
    }

    public function communications()
    {
        return $this->hasMany(EquipmentWaitListCommunication::class)->latest()->latest('id');
    }

    public function alerts()
    {
        return $this->hasMany(EquipmentWaitListAlert::class)->latest()->latest('id');
    }

    public function convertedOrder()
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    /** Records that still represent live demand (Active + Acknowledged). */
    public function scopeWaiting(Builder $q): Builder
    {
        return $q->whereIn('status', WaitListStatus::waiting());
    }

    /** Manual queue positions first (#1 → #3), then oldest first. */
    public function scopeByUrgency(Builder $q): Builder
    {
        return $q->orderByRaw('priority_override IS NULL')
            ->orderBy('priority_override')
            ->orderBy('created_at');
    }

    /**
     * Customer said YES but no order has been converted yet. Acceptance is
     * not a completed sale: these records stay in the live waiting queue
     * (status Acknowledged) and carry this distinct state until they are
     * converted, cancelled, or re-disposed — they can never silently drop
     * out of the actionable workflow.
     */
    public function scopeAcceptedAwaitingConversion(Builder $q): Builder
    {
        return $q->waiting()->whereHas('alerts', fn ($a) => $a
            ->where('disposition', \App\Enums\WaitList\WaitListAlertDisposition::CustomerAccepted->value));
    }

    /** Whether this record is an accepted-but-unconverted opportunity. */
    public function isAcceptedAwaitingConversion(): bool
    {
        if (! in_array($this->status->value, WaitListStatus::waiting(), true)) {
            return false;
        }

        // Prefer a withExists('alerts as has_accepted_alert') column when the
        // caller provided one (index lists) to avoid per-row queries.
        if (array_key_exists('has_accepted_alert', $this->attributes)) {
            return (bool) $this->attributes['has_accepted_alert'];
        }

        $alerts = $this->relationLoaded('alerts') ? $this->alerts : $this->alerts()->get();

        return $alerts->contains(fn ($alert) => $alert->disposition === \App\Enums\WaitList\WaitListAlertDisposition::CustomerAccepted);
    }

    // ── Lifecycle (all manual — no automation by design) ───────────

    public function markAcknowledged(): void
    {
        if ($this->status === WaitListStatus::Active) {
            $this->update(['status' => WaitListStatus::Acknowledged]);
        }
    }

    /** Link the staff-created reservation/order and close out the record. */
    public function convert(int $orderId): void
    {
        $this->update([
            'status'             => WaitListStatus::Converted,
            'converted_order_id' => $orderId,
            'converted_at'       => now(),
        ]);
    }

    /** Manual cancellation only — records stay searchable forever. */
    public function cancel(): void
    {
        $this->update([
            'status'       => WaitListStatus::Cancelled,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);
    }

    // ── Presentation ───────────────────────────────────────────────

    /** Whole days this record has been waiting. */
    public function getAgeDaysAttribute(): int
    {
        return (int) $this->created_at->startOfDay()->diffInDays(now()->startOfDay());
    }

    /** What the customer is waiting for, in one line. */
    public function demandLabel(): string
    {
        if ($this->relationLoaded('selectedProducts') ? $this->selectedProducts->isNotEmpty() : $this->selectedProducts()->exists()) {
            $category = $this->category?->title;
            $count = $this->relationLoaded('selectedProducts')
                ? $this->selectedProducts->count()
                : $this->selectedProducts()->count();

            return trim(($category ?? 'Equipment') . ' — ' . $count . ' acceptable ' . Str::plural('product', $count));
        }

        // Legacy fallbacks: un-migrated historical records
        if ($this->request_type === WaitListRequestType::Category) {
            return $this->category?->title ?? 'Category';
        }

        return $this->items->map(fn ($i) => $i->equipment?->equipment_name ?? "Equipment #{$i->equipment_id}")
            ->filter()->implode(', ') ?: 'Specific equipment';
    }
}

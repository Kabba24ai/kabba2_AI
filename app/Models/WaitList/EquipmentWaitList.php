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
 * with one or more selected acceptable EQUIPMENT INVENTORY UNITS attached
 * (individual assets by Equipment ID — never catalog products, and no
 * quantity support by design). The unit set is a snapshot owned by the
 * record — later inventory or category changes never silently alter an
 * existing request. No automatic customer notifications, reservations,
 * holds, or expiration — every contact and disposition decision is manual.
 *
 * equipment_wait_list_items is the canonical selection store. The
 * equipment_wait_list_products pivot remains ONLY as an audit trail and
 * matching fallback for records created during the short product-based
 * window; the corrective migration logs those for manual re-selection.
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

    /** CANONICAL: the selected acceptable equipment inventory units. */
    public function items()
    {
        return $this->hasMany(EquipmentWaitListItem::class);
    }

    /**
     * Product-era audit trail (records created through the short-lived
     * product-based form). Never written for new records; the matcher
     * honors it only as a fallback when a record has no unit selections.
     */
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
        $unitCount = $this->relationLoaded('items') ? $this->items->count() : $this->items()->count();

        if ($unitCount > 0) {
            return trim(($this->category?->title ?? 'Equipment') . ' — ' . $unitCount . ' acceptable ' . Str::plural('unit', $unitCount));
        }

        // Product-era fallback: created via the short-lived product form
        $productCount = $this->relationLoaded('selectedProducts')
            ? $this->selectedProducts->count()
            : $this->selectedProducts()->count();

        if ($productCount > 0) {
            return trim(($this->category?->title ?? 'Equipment') . ' — ' . $productCount . ' acceptable ' . Str::plural('product', $productCount));
        }

        // Legacy category fallback (no units existed at correction time)
        return $this->category?->title ?? 'Equipment request';
    }
}

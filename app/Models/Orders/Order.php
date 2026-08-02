<?php

namespace App\Models\Orders;

use App\Enums\Orders\OrderMediaType;
use App\Enums\Equipments\EquipmentCurrentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

// Enums
use App\Enums\Orders\OrderTermsStatus;

// Helpers
use App\Helpers\ModelHelper;
use App\Helpers\CustomHelper;

// Models
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Customers\Customer;
use App\Models\Customers\Invoice;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Services\Orders\OrderPaymentSummary;
use stdClass;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unique_id',
        'reference_order_number',
        'order_number',
        'invoice_id',
        'order_date',
        'order_time', // New column for order time
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'company_name',
        'company_website',
        'subtotal',
        'is_tax_exempt', // Yes, No*
        'tax_amount',
        'special_tax_amount',
        'added_fees_amount',
        'coupon_code',
        'discount_amount',
        'grand_total',
        'pretax_discount_total',
        'tax_amount_before_discount',
        'grand_total_before_discount',
        'auto_inject',
        'auto_inject_by',
        'order_note',
        'cart_data', // JSON data of cart items
        'platform', // Web*, Android, iOS
        'po_id',
        'terms_collection', // use that collection to get original terms content
        'pending_terms_content',
        'accepted_terms_content', // Content of terms that were accepted
        'terms_accepted_at', // DateTime when terms were accepted
        'terms_status', // Accepted, Declined, Pending*, Exempt
        'last_terms_sms_sent_at',
        'terms_first_sent_at',
        'terms_first_message_id',
        'terms_second_sent_at',
        'terms_second_message_id',
        'terms_third_sent_at',
        'terms_third_message_id',
        'signature_image', // Base64 encoded image of signature

        'receipt_status',
        'deleted_by',
    ];

    protected $casts = [
        'cart_data' => 'array',
        'auto_inject' => 'boolean',
        'terms_collection' => 'array',
        'terms_status' => OrderTermsStatus::class,
    ];

    protected $appends = [
        'view_link', // For generating view link in schedules
        'last_payment_type',
        'last_payment_status',
        'is_paid',
    ];

    // Customer relationship (if you want)
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function referenceOrder()
    {
        return $this->belongsTo(Order::class, 'reference_order_number', 'order_number');
    }

    public function relatedOrders()
    {
        return $this->hasMany(Order::class, 'reference_order_number', 'order_number');
    }

    /**
     * True when this order is an extension child ("{parent}-A") created by
     * Extension\StoreController. Reorders also carry reference_order_number
     * but receive a fresh sequential order_number, never a suffixed one.
     */
    public function isExtensionChild(): bool
    {
        return !empty($this->reference_order_number)
            && str_starts_with((string) $this->order_number, $this->reference_order_number . '-');
    }

    public function scopeExtensionChildren($query)
    {
        return $query->whereNotNull('reference_order_number')
            ->whereRaw("order_number LIKE CONCAT(reference_order_number, '-%')");
    }

    /**
     * True when a signed Terms & Conditions document exists for this order —
     * the frozen accepted_terms_content snapshot captured at signing time.
     * Declined / Exempt / Pending / legacy-null orders have no signed document.
     */
    public function hasSignedTerms(): bool
    {
        return $this->terms_status?->isAccepted() === true
            && filled($this->accepted_terms_content);
    }

    /**
     * Payment Architecture Finalization (Tier 2) — filter semantics for
     * "Pending"/"Failed" payment_status filters. A raw status value like
     * 'Paid' or 'Refunded' unambiguously means "this order has a row with
     * that status" — those states don't go stale. 'Pending'/'Failed' are
     * different: a declined card attempt or an abandoned COD placeholder
     * stops being operationally relevant the moment the order is later
     * fully paid by some other combination of rows — an employee filtering
     * an order list by "Failed" is looking for orders that STILL need
     * attention, not a permanent historical tag. This scope expresses that
     * distinction explicitly (see OrderPaymentSummary::
     * unresolvedPaymentAttempts for the equivalent PHP-side rule, applied
     * once a single order is already loaded) rather than silently
     * reinterpreting what the filter means: 'Pending'/'Failed' additionally
     * require the order not be fully paid; every other status keeps the
     * simple "any row has this status" meaning.
     */
    public function scopeWherePaymentStatusFilter($query, string $status)
    {
        $query->whereHas('payments', fn ($q) => $q->where('status', $status));

        if (!in_array($status, [\App\Enums\Orders\OrderPaymentStatus::Pending->value, \App\Enums\Orders\OrderPaymentStatus::Failed->value], true)) {
            return $query;
        }

        $settledValues = collect(\App\Enums\Orders\OrderPaymentStatus::cases())
            ->filter(fn ($s) => $s->isSettled() || $s === \App\Enums\Orders\OrderPaymentStatus::PartialPayment)
            ->map(fn ($s) => $s->value)
            ->all();

        return $query->whereRaw(
            '(SELECT COALESCE(SUM(op_settled.amount), 0) FROM order_payments op_settled
              WHERE op_settled.order_id = orders.id AND op_settled.status IN (' . implode(',', array_fill(0, count($settledValues), '?')) . ')
             ) < (orders.grand_total - 0.005)',
            $settledValues
        );
    }

    /**
     * The Rental Extension BillingCharge this order was created BY (set only
     * on extension child orders). The charge and the child order share one
     * lifecycle — see ExtensionTransactionService.
     */
    public function extensionCharge()
    {
        return $this->hasOne(BillingCharge::class, 'child_order_id')
            ->where('billing_charge_type', \App\Enums\Billing\BillingChargeType::Extension->value)
            ->latest('id');
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class, 'order_id');
    }

    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class, 'order_id')->where('type', 'Shipping');
    }

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class, 'order_id')->where('type', 'Billing');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function podPaymentLink()
    {
        return $this->hasOne(PodPaymentLink::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function lastPayment()
    {
        return $this->hasOne(OrderPayment::class, 'order_id')->latestOfMany('id');
    }

    public function lastRefundPayment()
    {
        return $this->hasOne(OrderPayment::class, 'order_id')->ofMany(
            ['id' => 'max'], // aggregate: take the row with max(id)
            fn($query) => $query->refund(), // constraint: only refund rows
        );
    }

    public function lastPaidPayment()
    {
        return $this->hasOne(OrderPayment::class, 'order_id')->ofMany(
            ['id' => 'max'], // aggregate: take the row with max(id)
            fn($query) => $query->paid(), // constraint: only paid rows
        );
    }

    public function history()
    {
        return $this->hasMany(OrderHistory::class, 'order_id');
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class, 'order_id')->latest('id');
    }

    public function media()
    {
        return $this->hasMany(OrderMedia::class, 'order_id');
    }

    public function licenseMedia()
    {
        return $this->hasMany(OrderMedia::class, 'order_id')->where('type', OrderMediaType::LICENSE)->orderByRaw("
                CASE
                    WHEN side = 'front' THEN 1
                    WHEN side = 'back' THEN 2
                    ELSE 3
                END
            ");
    }

    public function deliveryMedia()
    {
        return $this->hasMany(OrderMedia::class, 'order_id')->where('type', OrderMediaType::DELIVERY);
    }

    public function pickupMedia()
    {
        return $this->hasMany(OrderMedia::class, 'order_id')->where('type', OrderMediaType::PICKUP);
    }

    // Polymorphic relations for created_by and updated_by
    public function createdBy()
    {
        return $this->morphTo();
    }

    public function updatedBy()
    {
        return $this->morphTo();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'ORD');

            if (empty($model->order_number)) {
                // Get latest order ID including soft deleted
                $latestOrder = self::withTrashed()->latest('id')->first();
                $nextId = $latestOrder ? $latestOrder->id + 1 : 1;

                // Format: #001
                $model->order_number = '#' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            // Set current date and time
            $currentDateTime = Carbon::now();
            $model->order_date = $currentDateTime->format(config('app.date.db_date_format'));
            $model->order_time = $currentDateTime->format('H:i:s');
        });

        // On soft-delete: move all assigned equipment to Maintenance hold
        static::deleting(function ($model) {
            // Record who deleted this order
            if (!$model->isForceDeleting()) {
                $model->deleted_by = auth()->id();
                $model->saveQuietly();

                // Stop all CRM Funnel SMS for this order BEFORE products are deleted.
                // Creates permanent 'Stopped' log entries so communication history is preserved.
                \App\Services\FunnelLifecycleService::stopFunnelsForOrder(
                    $model,
                    \App\Services\FunnelLifecycleService::REASON_ORDER_DELETED
                );
            }

            $model->products()->with('equipment')->get()->each(function ($product) use ($model) {
                $equipment = $product->equipment;
                if ($equipment) {
                    $beforeStatus = $equipment->current_status?->value;
                    $equipment->current_status          = EquipmentCurrentStatus::Maintenance->value;
                    $equipment->current_status_updated_by = auth()->id();
                    $equipment->current_status_changed_at = now();
                    $equipment->current_order_id          = null;
                    $equipment->current_order_product_id  = null;
                    $equipment->saveQuietly();
                    EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Maintenance->value, auth()->id());
                }

                if (!$model->isForceDeleting()) {
                    // Use individual delete() so OrderProduct's deleting hook fires
                    // (cascades soft-delete to softAssignment, orderMedia, checklist questions/answers)
                    $product->delete();
                }
            });

            if (!$model->isForceDeleting()) {
                $model->addresses()->delete();
                $model->payments()->delete();
                $model->history()->delete();
                $model->notes()->delete();
                $model->media()->delete();
                $model->extraCharges()->delete();
                $model->softAssignments()->delete();
            }
        });

        static::restoring(function ($model) {
            $model->addresses()->withTrashed()->restore();
            $model->payments()->withTrashed()->restore();
            $model->history()->withTrashed()->restore();
            $model->notes()->withTrashed()->restore();
            $model->media()->withTrashed()->restore();
            $model->extraCharges()->withTrashed()->restore();
            $model->softAssignments()->withTrashed()->restore();
            // Use individual restore() so OrderProduct's restoring hook fires
            // (cascades restore to softAssignment, orderMedia, checklist questions/answers)
            $model->products()->withTrashed()->get()->each(function ($product) {
                $product->restore();
            });
        });

        // On force-delete: clean up order media files
        static::forceDeleting(function ($model) {
            $model->media()->withTrashed()->get()->each(function ($child) {
                $child->forceDelete(); // Triggers deleting event on OrderMedia
            });
        });
    }

    public function getCustomerPhoneAttribute($value)
    {
        return empty($value) ? $value : CustomHelper::formatPhone($value);
    }

    public function getViewLinkAttribute()
    {
        $url = route('admin.order-management.orders.edit', ['unique_id' => $this->unique_id]);
        return '<a href="' . $url . '" class="text-brand-500 underline font-bold">' . $this->order_number . '</a>';
    }

    public function getLastPaymentTypeAttribute()
    {
        $lastPayment = $this->lastPayment;
        return $lastPayment ? $lastPayment->payment_method : null;
    }

    public function getLastPaymentStatusAttribute()
    {
        $lastPayment = $this->lastPayment;
        return $lastPayment ? $lastPayment->status->value : null;
    }

    /**
     * Paid in Full is derived from the aggregate settled-payments total
     * against grand_total — NOT from any single payments() row individually
     * carrying status Paid. Two PartialPayment rows (e.g. $500 Cash + $500
     * Card on a $1,000 order) together satisfy the order even though
     * neither row is itself status Paid; the old row-existence check never
     * flipped true for that case. A small epsilon absorbs rounding, same
     * convention already used by ReceivePaymentController's own
     * partial-completes-total check.
     */
    public function getIsPaidAttribute()
    {
        return ($this->total_paid + 0.005) >= (float) $this->grand_total;
    }

    /**
     * Total Settled Payments — sums every payments() row that represents
     * real, settled money (Paid, Partial Payment, or a legacy Invoice*
     * status), via OrderPayment::scopeSettled(). Previously hardcoded to
     * [PartialPayment, Paid] only, which silently excluded settled
     * Invoice* rows from this total.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->settled()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0.0, (float) $this->grand_total - $this->total_paid);
    }

    /**
     * Total Successful Refunds. A refund does not reduce Settled Payments
     * (the customer did pay it); it reduces Net Paid / the order's
     * refundable balance instead (see
     * getNetPaidAttribute()/getRemainingAmountAttribute()).
     *
     * Phase 3C fix: previously summed refund_amount directly across every
     * PartialRefund/Refund row — correct when a refund row could only ever
     * be a single, atomic success (Phase 3A/3B), but a multi-source refund
     * can now PARTIALLY fail (see PaymentAllocationService::syncRefundOperationOutcome()),
     * and refund_amount continues to represent the ORIGINALLY REQUESTED
     * total for that event, not what actually succeeded. Delegates to
     * PaymentAllocationService::totalSuccessfulRefunded(), which counts
     * only the successful (Allocated) allocation amounts — falling back to
     * a row's raw refund_amount only for legacy refunds that predate the
     * allocation table and have not yet been backfilled, unchanged from
     * Phase 3B.
     */
    public function getTotalRefundedAttribute()
    {
        return \App\Services\Orders\PaymentAllocationService::totalSuccessfulRefunded($this);
    }

    /**
     * Order Refundable Balance = Total Settled Payments − Total Successful
     * Refunds. This is the order-level cap a refund request must never
     * exceed.
     *
     * Previously computed as (grand_total − total_refunded), which is
     * anchored to the wrong base for a partially-paid order: a $1,000
     * order with only $400 actually collected must never allow refunding
     * more than $400, regardless of what grand_total says. The two
     * formulas coincide once an order is fully paid (total_paid ==
     * grand_total), which is why this went unnoticed — they diverge
     * exactly on a partial payment. Kept under the original accessor name
     * (remaining_amount) since it's read from ~10 places in the refund
     * modal; only the formula changed, not the name or call sites.
     */
    public function getRemainingAmountAttribute()
    {
        return max(0.0, (float) $this->total_paid - (float) $this->total_refunded);
    }

    /**
     * Net Paid — the exact same figure as the refundable-balance cap
     * above, exposed under its own name because it answers a different
     * question for display purposes: "how much of this order's money is
     * currently, actually with the business," not "how much more can
     * still be refunded." Deliberately delegates to
     * getRemainingAmountAttribute() rather than repeating the formula —
     * there is exactly one place this arithmetic is written.
     */
    public function getNetPaidAttribute(): float
    {
        return $this->remaining_amount;
    }

    /**
     * Per-payment remaining refundable balance for a single original
     * payment row. This enforces the SECOND, narrower cap the refund flow
     * must respect alongside the order-level cap above — a refund must
     * never exceed whichever of the two is smaller.
     *
     * Phase 3B: delegates to PaymentAllocationService, the canonical
     * source for allocation-aware remaining-refundable math (with a
     * built-in legacy fallback for payments that predate the allocation
     * table and have not yet been backfilled) — this method is kept only
     * so existing call sites don't need to know the service exists.
     */
    public function remainingRefundableForPayment(OrderPayment $payment): float
    {
        return \App\Services\Orders\PaymentAllocationService::remainingRefundable($payment);
    }

    /**
     * The canonical, multi-payment-aware payment/refund summary for this
     * order — see App\Services\Orders\OrderPaymentSummary. Built fresh on
     * every call (not cached on the model) so it always reflects the
     * payments() collection as currently loaded/queried.
     */
    public function paymentSummary(): OrderPaymentSummary
    {
        return OrderPaymentSummary::for($this);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function latestReceipt()
    {
        return $this->hasOne(\App\Models\Customers\Receipt::class)->latestOfMany(); // Laravel helper
    }

    public function extraCharges()
    {
        return $this->hasMany(OrderExtraCharges::class, 'order_id', 'id')->latest();
    }

    public function billingCharges()
    {
        return $this->hasMany(\App\Models\Orders\BillingCharge::class, 'parent_order_id')->latest();
    }

    public function softAssignments()
    {
        return $this->hasMany(EquipmentSoftAssign::class, 'order_id', 'id');
    }

    /**
     * Build a single feed for all order-related notes shown in admin order edit.
     */
    public function getUnifiedNotesAttribute()
    {
        $orderNotes = $this->notes()
            ->with(['createdBy', 'updatedBy'])
            ->get()
            ->map(function ($note) {
                $note->source_label = 'Order Note';
                $note->is_editable = true; // All order notes are editable/deletable in the admin interface
                return $note;
            });

        $paymentNotes = $this->payments()
            ->with('createdBy')
            ->whereNotNull('payment_note')
            ->where('payment_note', '!=', '')
            ->get()
            ->map(function ($payment) {
                $row = new stdClass();
                $row->id = 'payment-' . $payment->id;
                $row->note = $payment->payment_note;
                $row->created_at = $payment->created_at;
                $row->updated_at = $payment->updated_at;
                $row->createdBy = $payment->createdBy;
                $row->updatedBy = null;
                $row->created_by_type_name = $payment->createdBy ? class_basename($payment->createdBy) : null;
                $row->updated_by_type_name = null;
                $row->source_label = 'Payment Note';
                $row->context_label = $payment->payment_method?->label();
                $row->is_editable = false;
                return $row;
            });

        $orderProducts = $this->products()
            ->with(['deliveryEmployee', 'pickupEmployee'])
            ->get();

        $deliveryNotes = $orderProducts->filter(fn($product) => filled($product->delivery_notes))->map(function ($product) {
            $row = new stdClass();
            $row->id = 'delivery-' . $product->id;
            $row->note = $product->delivery_notes;
            $row->created_at = $product->updated_at ?? $product->created_at;
            $row->updated_at = $product->updated_at;
            $row->createdBy = $product->deliveryEmployee;
            $row->updatedBy = null;
            $row->created_by_type_name = $product->deliveryEmployee ? class_basename($product->deliveryEmployee) : null;
            $row->updated_by_type_name = null;
            $row->source_label = 'Delivery Note';
            $row->context_label = $product->product_name;
            $row->is_editable = false;
            return $row;
        });

        $pickupNotes = $orderProducts->filter(fn($product) => filled($product->pickup_notes))->map(function ($product) {
            $row = new stdClass();
            $row->id = 'pickup-' . $product->id;
            $row->note = $product->pickup_notes;
            $row->created_at = $product->updated_at ?? $product->created_at;
            $row->updated_at = $product->updated_at;
            $row->createdBy = $product->pickupEmployee;
            $row->updatedBy = null;
            $row->created_by_type_name = $product->pickupEmployee ? class_basename($product->pickupEmployee) : null;
            $row->updated_by_type_name = null;
            $row->source_label = 'Pickup Note';
            $row->context_label = $product->product_name;
            $row->is_editable = false;
            return $row;
        });

        return $orderNotes->concat($paymentNotes)->concat($deliveryNotes)->concat($pickupNotes)->sortByDesc(fn($note) => optional(data_get($note, 'created_at'))->timestamp ?? 0)->values();
    }
}

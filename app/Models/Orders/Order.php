<?php

namespace App\Models\Orders;

use App\Enums\Orders\OrderMediaType;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

// Enums
use App\Enums\Orders\OrderTermsStatus;

// Helpers
use App\Helpers\ModelHelper;

// Models
use App\Models\Customers\Customer;
use App\Models\Customers\Invoice;


class Order extends Model
{
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
        'coupon_code',
        'discount_amount',
        'grand_total',
        'order_note',
        'cart_data', // JSON data of cart items
        'platform', // Web*, Android, iOS

        'terms_collection', // use that collection to get original terms content
        'pending_terms_content',
        'accepted_terms_content', // Content of terms that were accepted
        'terms_accepted_at', // DateTime when terms were accepted
        'terms_status', // Accepted, Declined, Pending*, Exempt
        'last_terms_sms_sent_at',
        'signature_image', // Base64 encoded image of signature

        'receipt_status'
    ];

    protected $casts = [
        'cart_data' => 'array',
        'terms_collection' => 'array',
        'terms_status' => OrderTermsStatus::class,
    ];

    protected $appends = [
        'view_link', // For generating view link in schedules
        'last_payment_type',
        'last_payment_status',
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
        return $this->hasOne(OrderPayment::class, 'order_id') ->ofMany(
            ['id' => 'max'],              // aggregate: take the row with max(id)
            fn ($query) => $query->refund() // constraint: only refund rows
        );
    }

    public function lastPaidPayment()
    {
         return $this->hasOne(OrderPayment::class, 'order_id') ->ofMany(
            ['id' => 'max'],              // aggregate: take the row with max(id)
            fn ($query) => $query->paid() // constraint: only paid rows
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
        return $this->hasMany(OrderMedia::class, 'order_id')->where('type', OrderMediaType::LICENSE);
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

            // Get latest order ID
            $latestOrder = self::latest('id')->first();
            $nextId = $latestOrder ? $latestOrder->id + 1 : 1;

            // Format: ORD-0001
            $model->order_number = '#' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            // Set current date and time
            $currentDateTime = Carbon::now();
            $model->order_date = $currentDateTime->format(config('app.date.db_date_format'));
            $model->order_time = $currentDateTime->format('H:i:s');
        });

        static::deleting(function ($model) {
            $model->media->each(function ($child) {
                $child->delete(); // Triggers deleting event on OrderMedia
            });
        });
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
        return $lastPayment ? $lastPayment->status->label() : null;
    }

    public function getTotalRefundedAttribute()
    {
        return $this->payments()
            ->whereIn('status', [\App\Enums\Orders\OrderPaymentStatus::PartialRefund, \App\Enums\Orders\OrderPaymentStatus::Refund])
            ->sum('refund_amount');
    }

    public function getRemainingAmountAttribute()
    {
        return max(0, (float) $this->grand_total - (float) $this->total_refunded);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function latestReceipt()
    {
        return $this->hasOne(\App\Models\Customers\Receipt::class)
            ->latestOfMany(); // Laravel helper
    }

    public function extraCharges()
{
    return $this->hasMany(
        OrderExtraCharges::class,
        'order_id',
        'id'
    )->latest();
}


}

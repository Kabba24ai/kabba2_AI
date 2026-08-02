<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class Receipt extends Model
{
    protected $fillable = [
        'unique_id',
        'invoice_id',
        'customer_id',
        'order_id',
        'superseded_receipt_id',
        'goodwill_adjustment_id',
        'receipt_created_by',
        'payment_method',
        'receipt_date',
        'order_date',
        'payment_status',
        'subtotal',
        'sales_tax',
        'goodwill_amount',
        'total',
        'is_email_status',
        'mail_send_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'sales_tax'       => 'decimal:2',
        'goodwill_amount' => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'REC');
        });

        // The supersession chain must stay acyclic, and the shortest possible
        // cycle is a receipt pointing at itself. supersede() cannot produce one
        // — it always inserts a NEW row referencing a PRIOR id — but this is
        // the kind of invariant that is cheap to enforce and expensive to
        // discover broken: a self-referential row makes isSuperseded() true
        // forever, so getOrCreateReceipt() would serve a document that claims
        // to have replaced itself.
        self::saving(function ($model) {
            if ($model->superseded_receipt_id !== null
                && $model->exists
                && (int) $model->superseded_receipt_id === (int) $model->getKey()) {
                throw new \LogicException(
                    'A receipt cannot supersede itself (receipt '.$model->getKey().'). '
                    .'Supersession is append-only: a NEW receipt references the one it replaces.'
                );
            }
        });
    }

    /** Relationships */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(\App\Models\Orders\Order::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'receipt_created_by');
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    /** The receipt THIS one replaces. Null on an original. */
    public function supersededReceipt()
    {
        return $this->belongsTo(self::class, 'superseded_receipt_id');
    }

    /** The receipt that replaced this one, if any. */
    public function supersededBy()
    {
        return $this->hasOne(self::class, 'superseded_receipt_id');
    }

    public function goodwillAdjustment()
    {
        return $this->belongsTo(\App\Models\Orders\OrderGoodwillAdjustment::class, 'goodwill_adjustment_id');
    }

    /**
     * Has this receipt been replaced?
     *
     * Superseded receipts are never deleted or edited — they are the record of
     * what the customer was originally told — but only the newest is current.
     */
    public function isSuperseded(): bool
    {
        return $this->supersededBy()->exists();
    }

    /** Issued as a replacement rather than as an order's first receipt. */
    public function isSuperseding(): bool
    {
        return $this->superseded_receipt_id !== null;
    }
}

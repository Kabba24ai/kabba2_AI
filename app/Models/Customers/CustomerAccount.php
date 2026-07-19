<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Helpers\ModelHelper;
class CustomerAccount extends Model
{
    use SoftDeletes;
    protected $table = 'customer_accounts';

    protected $fillable = [
        'unique_id',
        'customer_id',
        'order_id',
        'balance',
        'amount',
        'payment_type',
        'responsible_person_id',
        'responsible_person_name',
        'notes',
        'date',
        'payment_number_id',
        'auth_code',
        'customer_profile_id',
        'payment_profile_id',
        'reason',
        'sales_tax',
        'sales_tax_type',
        'type',
        'invoice_id',
        'invoice_item_id',
        'customer_action_log',
        'fuel_alert_status',
        'damage_alert_status',
        'order_product_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'payment_type' => \App\Enums\Customers\PaymentMethod::class,
        'customer_action_log' => 'array',
    ];

     /**
     * Boot model: auto-generate unique_id before creating.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'ACC');
            }
        });
    }

    /**
     * Relationship to the Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // in CustomerAccount
    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /** Billing Charge Refund Allocation — set only for a linked refund (type='refund') row. */
    public function billingChargeRefund()
    {
        return $this->hasOne(\App\Models\Orders\BillingChargeRefund::class, 'customer_account_id');
    }

    /**
     * Relationship to the Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship to the originating OrderProduct (set for checklist-based charges).
     */
    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function card()
    {
        return $this->belongsTo(CustomerCard::class, 'payment_profile_id', 'payment_profile_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
 * Scope: Entries eligible for invoice
 */
public function scopeInvoiceEntries($query)
{
    return $query
        ->where('type', '!=', 'payment')
        ->where('type', '!=', 'account_invoice')
        ->whereNull('invoice_id')
        ->where(function ($q) {
            $q->where('type', '!=', 'order')
              ->orWhereIn('id', function ($sub) {
                  $sub->selectRaw('MIN(id)')
                      ->from('customer_accounts')
                      ->where('type', 'order')
                      ->whereNull('invoice_id')
                      ->groupBy('order_id');
              });
        });
}

}

<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use App\Models\Global\Media;
// Helpers
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Carbon\Carbon;
use Database\Factories\Customers\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    protected $fillable = [
        'unique_id',
        'first_name',
        'last_name',
        'company_name',

        'company_phone',
        'company_website',
        'tax_document_status',
        'email',
        'password',
        'media_id',
        'phone',
        'dob',
        'authorize_profile_id',
        'status', // Active*, Inactive, Archive
        'is_guest', // true or false
        'tax_status', // Taxable* , Exempt
        'tax_document_media_id',
        'tax_document_upload_date',
        'tax_document_valid_until',
        'is_credit_account', // true of false
        'credit_limit',
        'account_approved_by',
        'account_application_completed',
        'tax_status_approved_by',
        'is_reset',
        'tax_document_type',

        'same_as_billing',
        'tags',

        'password_reset_token',
        'password_reset_token_expiry',

        'license_front_media_id',
        'license_back_media_id',
        'license_expiry_date',

    ];

    protected $appends = [
        'full_name',
        'is_tax_exempt_valid', // true = Exempt, false = Taxable
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'CUS');
        });
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function accountApprovedBy()
    {
        return $this->belongsTo(User::class, 'account_approved_by');
    }

    public function taxStatusApprovedBy()
    {
        return $this->belongsTo(User::class, 'tax_status_approved_by');
    }

    /**
     * Determine if the customer's tax-exempt status is currently valid.
     */
    public function getIsTaxExemptValidAttribute(): bool
    {
        // Must be 'Exempt'
        if ($this->tax_status !== 'Exempt') {
            return false;
        }

        // Must have a document uploaded
        // if (empty($this->tax_document_media_id)) {
        //     return false;
        // }

        // If both dates are given, check the date validity
        if (! empty($this->tax_document_upload_date) && ! empty($this->tax_document_valid_until)) {
            $today = Carbon::today();
            $uploadDate = Carbon::parse($this->tax_document_upload_date);
            $validUntil = Carbon::parse($this->tax_document_valid_until);

            return $today->between($uploadDate, $validUntil);
        }

        // if tax_status and dates are not given it means it applicable Exempt for lifetime
        return true;
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'tax_document_media_id', 'id');
    }

    public function licenseFront()
    {
        return $this->belongsTo(Media::class, 'license_front_media_id');
    }

    public function licenseBack()
    {
        return $this->belongsTo(Media::class, 'license_back_media_id');
    }

    public function orders()
    {
        return $this->morphMany(Order::class, 'created_by');
    }

    public function getTotalOrderAmountAttribute()
    {
        return $this->orders()->sum('grand_total');
    }

    public function getTotalAccountOrderAmountAttribute()
    {
        return $this->orders()
            ->whereHas('payments', function ($query) {
                $query->where('payment_method', 'Account')->whereRaw('id = (SELECT MIN(id) FROM order_payments WHERE order_id = orders.id)');
            })
            ->sum('grand_total');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('type', 'Billing')->primary();
    }

    public function shippingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('type', 'Shipping')->primary();
    }

    public function accounts()
    {
        return $this->hasMany(CustomerAccount::class)->orderBy('id', 'desc');
    }

    /**
     * Phase 3.0/3.1 — Financial Store Credit ledger (grants/redemptions).
     * Deliberately a separate relationship from accounts() — see
     * app/Services/CustomerCreditService.php for why this is not part of
     * the customer_accounts ledger.
     */
    public function customerCredits()
    {
        return $this->hasMany(CustomerCredit::class)->orderBy('created_at')->orderBy('id');
    }

    // CustomerAccount where type is payment
    public function paymentAccounts()
    {
        return $this->hasMany(CustomerAccount::class)
            ->where('type', 'payment')
            ->orderBy('date', 'desc');
    }

    public function cards()
    {
        return $this->hasMany(CustomerCard::class);
    }

    /**
     * Final Phase — Refund Consumer Cleanup: this used to join each order
     * to only its single highest-id order_payments row (the same MAX(id)
     * attribution flaw PaymentReconciliationLedger::streamA() had) and sum
     * the WHOLE order's grand_total whenever that one row's status was
     * Paid/Refunded/Partial Refund — meaning a refunded order still
     * counted its full original price as "paid," and a split-payment
     * order's total depended on which payment happened to be entered
     * last. Now sums Order::net_paid (grand_total's canonical,
     * allocation-aware "money currently, actually with the business"
     * figure — see Order::getNetPaidAttribute()) across every order with
     * at least one settled payment, so a refund correctly reduces this
     * total instead of being ignored.
     */
    public function getPaidSalesAttribute()
    {
        return $this->orders()
            ->whereHas('payments', fn ($query) => $query->settled())
            ->get()
            ->sum(fn ($order) => $order->net_paid);
    }

    /**
     * Payment Architecture Finalization: this had the same MAX(id)
     * attribution flaw getPaidSalesAttribute() was already fixed for above
     * — it joined each order to only its single highest-id order_payments
     * row and summed the WHOLE order's grand_total whenever that one row
     * happened to be Pending, ignoring however much of the order had
     * already been settled by an earlier payment. Now sums each such
     * order's actual outstanding balance (Order::balance_due, the
     * canonical "not yet collected" figure) instead of the full
     * grand_total, so a partially-paid order with a pending second payment
     * only counts the remaining uncollected portion as "pending."
     */
    public function getPendingSalesAttribute()
    {
        return $this->orders()
            ->whereHas('payments', fn ($query) => $query->where('status', 'Pending'))
            ->get()
            ->sum(fn ($order) => $order->balance_due);
    }

    public function getTaxStatus(): string
    {
        return $this->tax_status ?? 'Taxable';
    }

    public function getLastPaymentAttribute()
    {
        return $this->accounts()->where('type', 'payment')->orderByDesc('date')->first();
    }

    public function getDaysSinceLastPaymentAttribute()
    {
        $lastPayment = $this->last_payment;

        if (! $lastPayment || ! $lastPayment->date) {
            return null;
        }

        return Carbon::parse($lastPayment->date)->diffInDays(Carbon::now());
    }

    public function getPaymentStatusBadgeAttribute()
    {
        $days = $this->days_since_last_payment;

        if ($days === null) {
            return 'no-payment'; // No payment yet
        }

        if ($days <= 30) {
            return 'safe'; // Green
        } elseif ($days <= 45) {
            return 'warning'; // Dark Yellow
        } else {
            return 'danger'; // Pink
        }
    }

    /**
     * Get all  invoices for the customer.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get total paid invoice amount for the customer.
     */
    public function getTotalPaidInvoicesAttribute()
    {
        return $this->invoices()
            ->where('invoice_status', 'paid')
            ->sum('total');
    }

    /**
     * Get total pending invoice amount for the customer.
     */
    public function getTotalPendingInvoicesAttribute()
    {
        return $this->invoices()
            ->where('invoice_status', 'pending')
            ->sum('total');
    }

    /**
     * Get total overdue invoice amount for the customer.
     */
    public function getTotalOverdueInvoicesAttribute()
    {
        return $this->invoices()
            ->where('invoice_status', 'overdue')
            ->sum('total');
    }

    /**
     * Get total number of unpaid invoices for the customer.
     */
    public function getUnpaidInvoicesCountAttribute()
    {
        return $this->invoices()
            ->where('invoice_status', '!=', 'paid')
            ->count();
    }

    /**
     * Get Notes for the customer.
     */
    public function notes()
    {
        return $this->hasMany(\App\Models\Customers\CustomerNote::class);
    }

    public function getTagObjectsAttribute()
    {
        if (empty($this->tags)) {
            return collect();
        }

        // Decode JSON if it's JSON; fallback to comma-separated format
        $tagIds = is_array($this->tags)
            ? $this->tags
            : (json_decode($this->tags, true) ?: explode(',', $this->tags));

        $tagIds = array_filter($tagIds);

        return Tag::whereIn('id', $tagIds)->get();
    }

    public function getTagsArrayAttribute()
    {
        return $this->tags ? array_map('trim', explode(',', $this->tags)) : [];
    }

    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany('invoice_date');
    }

    public function funnels()
    {
        return $this->belongsToMany(
            SalesFunnel::class,
            'customer_sales_funnels',
            'customer_id',
            'sales_funnel_id'
        )->withTimestamps();
    }
}

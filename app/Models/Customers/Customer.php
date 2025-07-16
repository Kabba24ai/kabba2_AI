<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


// Helpers
use App\Helpers\ModelHelper;
use App\Models\Orders\Order;
use Carbon\Carbon;
use App\Models\Global\Media;
use App\Models\Iam\Personnel\User;



class Customer extends Authenticatable
{
    use Notifiable;

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
        'tax_document_type'
    ];

    protected $appends = [
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
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
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
     *
     * @return bool
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
        if (!empty($this->tax_document_upload_date) && !empty($this->tax_document_valid_until)) {
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

    public function orders()
    {
        return $this->morphMany(Order::class, 'created_by');
    }

    public function getTotalOrderAmountAttribute()
    {
        return $this->orders()->sum('grand_total');
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('type', 'Billing');
    }

    public function shippingAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('type', 'Shipping');
    }
}

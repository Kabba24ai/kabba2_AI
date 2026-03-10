<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

use App\Models\Iam\Personnel\User ;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'customer_id',
        'invoice_created_by',
        'subtotal',
        'sales_tax',
        'total',
        'invoice_notes',
        'invoice_status',
        'is_email_send',
        'payment_method',
        'mail_send_at',
        'invoice_type',
        'paid_amount',
        'open_amount',
        'is_mail',
        'is_mail_date',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'INV');
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'invoice_created_by');
    }

}

<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class SmsBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_cat_id',
        'name',
        'description',
        'send_date',
        'status',
    ];

    // Relationship: each broadcast belongs to a category
    public function category()
    {
        return $this->belongsTo(SmsCategory::class, 'sms_cat_id');
    }
}

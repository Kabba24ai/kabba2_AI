<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class SmsFunnel extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_cat_id',
        'name',
        'description',
        'sales_funnels',
        'status',
    ];

    // Relationship: each broadcast belongs to a category
    public function category()
    {
        return $this->belongsTo(SmsCategory::class, 'sms_cat_id');
    }
}

<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\ModelHelper;

class SmsCategory extends Model
{
    protected $table = 'sms_categories';

    protected $fillable = [
        'unique_id',
        'name',
        'description',
    ];

    // Auto-generate UUID when creating
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SMS-CAT');
        });
    }
}

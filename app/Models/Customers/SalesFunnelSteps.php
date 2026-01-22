<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

// Models
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\SmsCategory;

class SalesFunnelSteps extends Model
{
    protected $table = 'sales_funnel_steps';

    protected $fillable = [
        'unique_id',
        'sales_funnel_id',
        'step_type',
        'sms_category_id',
        'sms_message_id',
        'name',
        'message',
        'delay_unit',
        'delay_value',
        'sort_order',
    ];

    // Auto-generate UUID when creating
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SF-STEP');
        });
    }

    public function salesFunnel()
    {
        return $this->belongsTo(SalesFunnel::class, 'sales_funnel_id');
    }

    public function smsCategory()
    {
        return $this->belongsTo(SmsCategory::class, 'sms_category_id');
    }

    public function smsMessage()
    {
        return $this->belongsTo(SmsFunnel::class, 'sms_message_id');
    }
}

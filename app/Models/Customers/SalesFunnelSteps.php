<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

// Models
use App\Models\Customers\SalesFunnel;
use App\Models\Customers\SmsCategory;
use App\Models\Customers\EmailCategory;
use App\Models\Customers\EmailTemplate;

class SalesFunnelSteps extends Model
{
    protected $table = 'sales_funnel_steps';

    protected $fillable = [
        'unique_id',
        'sales_funnel_id',
        'step_type',
        'timing_reference_type',
        'offset_direction',
        'offset_days',
        'offset_hours',
        'offset_minutes_val',
        'offset_minutes',       // computed total (days*1440 + hours*60 + minutes_val)
        'message_category_id',
        'message_template_id',
        'sms_category_id',
        'sms_message_id',
        'email_category_id',
        'name',
        'message',
        'sort_order',
        'is_active',
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

    public function emailCategory()
    {
        return $this->belongsTo(EmailCategory::class, 'message_category_id');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'message_template_id');
    }
}

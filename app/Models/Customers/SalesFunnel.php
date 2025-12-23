<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;

class SalesFunnel extends Model
{
    protected $table = 'sales_funnels';

    protected $fillable = [
        'unique_id',
        'funnel_name',
        'description',
        'sales_funnel_category_id',
        'trigger_event',
        'trigger_event_timing',
      
        'minute_value',
        'hour_value',
        'date_value',

       'status', 

    ];

    // Auto-generate UUID when creating
    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'SFN');
        });
    }

     /**
     * 🔗 Funnel belongs to a category
     */
    public function category()
    {
        return $this->belongsTo(
            SalesFunnelCategory::class,
            'sales_funnel_category_id'
        );
    }

}

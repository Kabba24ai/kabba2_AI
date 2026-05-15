<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ModelHelper;
use App\Models\Orders\OrderProductFunnelLog;
use App\Models\ProductManagement\Product;

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

    /** scope: active */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeBeforeEvent($query)
    {
        return $query->where('trigger_event_timing', 'Before Event');
    }

    public function scopeAfterEvent($query)
    {
        return $query->where('trigger_event_timing', 'After Event');
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

    /**
     * 🔗 Funnel has many products
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'sales_funnel_products',
            'sales_funnel_id',
            'product_id'
        );
    }

    public function funnelLogs()
    {
        return $this->hasMany(OrderProductFunnelLog::class, 'sales_funnel_id');
    }

    /**
     * 🔗 Funnel has many steps
     */
    public function steps()
    {
        return $this->hasMany(SalesFunnelSteps::class, 'sales_funnel_id')->orderBy('sort_order');
    }

    public function customers()
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_sales_funnels',
            'sales_funnel_id',
            'customer_id'
        )->withTimestamps();
    }

}

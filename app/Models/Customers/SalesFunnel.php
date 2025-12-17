<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;

class SalesFunnel extends Model
{
    protected $table = 'sales_funnels';

    protected $fillable = [
        'funnel_name',
        'description',
        'sales_funnel_category_id',
        'trigger_event',
        'trigger_event_timing',
        'timing_unit',
        'timing_value',
        'is_active',
    ];
}

<?php

namespace App\Models\Dispatch;

use Illuminate\Database\Eloquent\Model;

class DispatchAiSettings extends Model
{
    protected $table = 'dispatch_ai_settings';

    protected $fillable = [
        'prefer_same_driver_for_returns',
        'ai_enabled',
        'cron_hour',
        'cron_minute',
        'look_ahead_days',
        'auto_build_draft',
        'allow_driver_assignment',
        'allow_truck_assignment',
        'allow_trailer_assignment',
        'allow_route_optimization',
        'allow_early_delivery',
        'early_delivery_enabled',
        'early_delivery_max_days',
        'early_delivery_window_start',
        'early_delivery_window_end',
        'early_delivery_customer_must_approve',
        'early_delivery_prioritize_weekend_specials',
        'early_delivery_reduce_friday_load',
        'route_efficiency_weight',
        'delivery_priority_weight',
        'driver_utilization_weight',
        'route_minimize_miles',
        'route_minimize_drive_time',
        'route_batch_nearby_deliveries',
        'route_batch_nearby_pickups',
        'route_keep_driver_near_home',
        'route_respect_delivery_windows',
    ];

    protected function casts(): array
    {
        return [
            'prefer_same_driver_for_returns'              => 'boolean',
            'ai_enabled'                                  => 'boolean',
            'auto_build_draft'                            => 'boolean',
            'allow_driver_assignment'                     => 'boolean',
            'allow_truck_assignment'                      => 'boolean',
            'allow_trailer_assignment'                    => 'boolean',
            'allow_route_optimization'                    => 'boolean',
            'allow_early_delivery'                        => 'boolean',
            'early_delivery_enabled'                      => 'boolean',
            'early_delivery_customer_must_approve'        => 'boolean',
            'early_delivery_prioritize_weekend_specials'  => 'boolean',
            'early_delivery_reduce_friday_load'           => 'boolean',
            'route_minimize_miles'                        => 'boolean',
            'route_minimize_drive_time'                   => 'boolean',
            'route_batch_nearby_deliveries'               => 'boolean',
            'route_batch_nearby_pickups'                  => 'boolean',
            'route_keep_driver_near_home'                 => 'boolean',
            'route_respect_delivery_windows'              => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'prefer_same_driver_for_returns'             => true,
            'ai_enabled'                                 => false,
            'cron_hour'                                  => 2,
            'cron_minute'                                => 0,
            'look_ahead_days'                            => 3,
            'auto_build_draft'                           => true,
            'allow_driver_assignment'                    => true,
            'allow_truck_assignment'                     => true,
            'allow_trailer_assignment'                   => true,
            'allow_route_optimization'                   => true,
            'allow_early_delivery'                       => true,
            'early_delivery_enabled'                     => false,
            'early_delivery_max_days'                    => 2,
            'early_delivery_window_start'                => null,
            'early_delivery_window_end'                  => null,
            'early_delivery_customer_must_approve'       => false,
            'early_delivery_prioritize_weekend_specials' => true,
            'early_delivery_reduce_friday_load'          => true,
            'route_efficiency_weight'                    => 40,
            'delivery_priority_weight'                   => 30,
            'driver_utilization_weight'                  => 30,
            'route_minimize_miles'                       => true,
            'route_minimize_drive_time'                  => false,
            'route_batch_nearby_deliveries'              => true,
            'route_batch_nearby_pickups'                 => true,
            'route_keep_driver_near_home'                => true,
            'route_respect_delivery_windows'             => true,
        ]);
    }
}

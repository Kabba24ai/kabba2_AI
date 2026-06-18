<?php

namespace App\Services\DispatchAI;

use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use App\Models\Dispatch\DispatchAiSettings;
use App\Models\Dispatch\DispatchAiTrailer;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;

class DispatchContextBuilder
{
    public function build(int $lookAheadDays): array
    {
        $settings = DispatchAiSettings::instance();
        $today    = Carbon::today();
        $horizon  = $today->copy()->addDays($lookAheadDays);

        // ── Open deliveries in window ─────────────────────────────────────────
        $deliveries = OrderProduct::with([
            'order.customer',
            'order.shippingAddress',
            'deliveryEmployee',
            'product.categories',
        ])
        ->whereHas('order')
        ->where('delivery_status', 'Pending')
        ->where('delivery_transport_mode', 'Truck')
        ->whereNotNull('delivery_date')
        ->whereDate('delivery_date', '>=', $today)
        ->whereDate('delivery_date', '<=', $horizon)
        ->orderBy('delivery_date')
        ->get();

        // ── Open returns in window ────────────────────────────────────────────
        $returns = OrderProduct::with([
            'order.customer',
            'order.shippingAddress',
            'pickupEmployee',
            'product.categories',
        ])
        ->whereHas('order')
        ->where('pickup_status', 'Pending')
        ->where('delivery_status', 'Completed')
        ->where('pickup_transport_mode', 'Truck')
        ->whereNotNull('pickup_date')
        ->whereDate('pickup_date', '>=', $today)
        ->whereDate('pickup_date', '<=', $horizon)
        ->orderBy('pickup_date')
        ->get();

        // ── Drivers ───────────────────────────────────────────────────────────
        $drivers     = User::active()->where('is_driver', true)->orderBy('first_name')->get();
        $capabilities = DispatchAiDriverCapability::whereIn('user_id', $drivers->pluck('id'))->get()->keyBy('user_id');

        $driverPayload = $drivers->map(function (User $d) use ($capabilities) {
            $cap = $capabilities->get($d->id);
            return [
                'id'                        => $d->id,
                'name'                      => $d->full_name,
                'cdl_a'                     => (bool) $d->cdl_a,
                'cdl_b'                     => (bool) $d->cdl_b,
                'cdl_license'               => (bool) $d->cdl_a || (bool) $d->cdl_b,
                'max_gvwr'                  => $cap?->max_gvwr,
                'max_trailer_weight'        => $cap?->max_trailer_weight,
                'can_tow_equipment_trailer' => $cap?->can_tow_equipment_trailer ?? false,
                'can_tow_gooseneck'         => $cap?->can_tow_gooseneck ?? false,
                'can_operate_cdl_truck'     => $cap?->can_operate_cdl_truck ?? false,
                'home_store_id'             => $cap?->home_store_id,
                'skill_rating'              => $cap?->skill_rating ?? 3,
            ];
        })->values()->toArray();

        // ── Trucks & Trailers ─────────────────────────────────────────────────
        $trucks   = DispatchAiTruck::with('store')->where('is_active', true)->get()->map(fn ($t) => [
            'id'           => $t->id,
            'name'         => $t->truck_name,
            'number'       => $t->truck_number,
            'store_id'     => $t->store_id,
            'gvwr'         => $t->gvwr,
            'tow_rating'   => $t->tow_rating,
            'hitch_types'  => $t->hitch_types,
            'cdl_required' => $t->cdl_required,
        ])->values()->toArray();

        $trailers = DispatchAiTrailer::with('store')->where('is_active', true)->get()->map(fn ($t) => [
            'id'               => $t->id,
            'name'             => $t->trailer_name,
            'number'           => $t->trailer_number,
            'store_id'         => $t->store_id,
            'gvwr'             => $t->gvwr,
            'payload_capacity' => $t->payload_capacity,
            'deck_length'      => $t->deck_length,
            'deck_width'       => $t->deck_width,
            'hitch_type'       => $t->hitch_type,
            'cdl_required'     => $t->cdl_required,
        ])->values()->toArray();

        // ── Equipment rules ───────────────────────────────────────────────────
        $equipmentRules = DispatchAiEquipmentRule::with('equipment.productCategory')->get()->map(fn ($r) => [
            'equipment_id'         => $r->equipment_id,
            'equipment_name'       => $r->equipment?->equipment_name,
            'category'             => $r->equipment?->productCategory?->title,
            'min_trailer_capacity' => $r->min_trailer_capacity,
            'allowed_trailer_types'=> $r->allowed_trailer_types,
            'allowed_truck_types'  => $r->allowed_truck_types,
            'cdl_required'         => $r->cdl_required,
            'can_share_trailer'    => $r->can_share_trailer,
            'must_haul_alone'      => $r->must_haul_alone,
            'special_notes'        => $r->special_notes,
        ])->values()->toArray();

        // ── Build order payloads ──────────────────────────────────────────────
        $deliveryPayload = $deliveries->map(fn ($op) => $this->formatOrderProduct($op, 'delivery'))->values()->toArray();
        $returnPayload   = $returns->map(fn ($op) => $this->formatOrderProduct($op, 'return'))->values()->toArray();

        return [
            'today'             => $today->toDateString(),
            'look_ahead_days'   => $lookAheadDays,
            'settings'          => [
                'prefer_same_driver_for_returns'             => $settings->prefer_same_driver_for_returns,
                'allow_early_delivery'                       => $settings->allow_early_delivery,
                'early_delivery_enabled'                     => $settings->early_delivery_enabled,
                'early_delivery_max_days'                    => $settings->early_delivery_max_days,
                'early_delivery_prioritize_weekend_specials' => $settings->early_delivery_prioritize_weekend_specials,
                'route_efficiency_weight'                    => $settings->route_efficiency_weight,
                'delivery_priority_weight'                   => $settings->delivery_priority_weight,
                'driver_utilization_weight'                  => $settings->driver_utilization_weight,
                'route_minimize_miles'                       => $settings->route_minimize_miles,
                'route_batch_nearby_deliveries'              => $settings->route_batch_nearby_deliveries,
                'route_batch_nearby_pickups'                 => $settings->route_batch_nearby_pickups,
                'route_keep_driver_near_home'                => $settings->route_keep_driver_near_home,
                'policy_overrides'                           => $settings->policy_overrides ?? [],
            ],
            'drivers'           => $driverPayload,
            'trucks'            => $trucks,
            'trailers'          => $trailers,
            'equipment_rules'   => $equipmentRules,
            'deliveries'        => $deliveryPayload,
            'returns'           => $returnPayload,
        ];
    }

    private function formatOrderProduct(OrderProduct $op, string $slot): array
    {
        $isDelivery    = $slot === 'delivery';
        $date          = $isDelivery ? $op->delivery_date : $op->pickup_date;
        $assignedDriver= $isDelivery ? $op->deliveryEmployee : $op->pickupEmployee;
        $driverLocked  = $isDelivery ? $op->delivery_driver_locked   : $op->pickup_driver_locked;
        $priorityLocked= $isDelivery ? $op->delivery_priority_locked  : $op->pickup_priority_locked;
        $priority      = $isDelivery ? $op->delivery_priority          : $op->pickup_priority;

        $address = $op->order?->shippingAddress;

        return [
            'order_product_id'  => $op->id,
            'order_number'      => $op->order?->order_number,
            'customer_name'     => $op->order?->customer_name,
            'product_name'      => $op->product_name,
            'category'          => $op->product?->categories?->first()?->title,
            'slot'              => $slot,
            'date'              => $date instanceof \Carbon\Carbon ? $date->toDateString() : (string) $date,
            'city'              => $address?->city,
            'state'             => $address?->state ?? '',
            'zip'               => $address?->zip_code ?? '',
            'latitude'          => $address?->latitude ?? null,
            'longitude'         => $address?->longitude ?? null,
            'assigned_driver_id'=> $assignedDriver?->id,
            'assigned_driver'   => $assignedDriver?->full_name,
            'driver_locked'     => (bool) $driverLocked,
            'priority'          => $priority,
            'priority_locked'   => (bool) $priorityLocked,
            // delivery driver info for prefer_same_driver_for_returns
            'delivery_driver_id'=> $isDelivery ? null : $op->deliveryEmployee?->id,
        ];
    }
}

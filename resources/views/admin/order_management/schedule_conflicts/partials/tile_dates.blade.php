{{--
    Grid tile date row.

    Mirrors the List View date columns exactly: same transport-mode icons
    (Truck / in-store pickup), same completed/pending color logic. Pass
    `forceDeliveryColor` / `forcePickupColor` to override the status colors
    where the List View hardcodes them (e.g. Overdue "upcoming" rows).
--}}
@php
    $tdDeliveryColor = $forceDeliveryColor ?? ($op->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600');
    $tdPickupColor   = $forcePickupColor   ?? ($op->pickup_status   === 'Completed' ? 'text-green-600' : 'text-yellow-600');
@endphp
<div class="mt-1.5 flex items-center justify-between gap-2 text-xs text-gray-600">
    <span class="inline-flex items-center gap-1">
        @if(!empty($op->delivery_transport_mode))
            @if($op->delivery_transport_mode === 'Truck')
                <x-heroicon-o-truck class="w-4 h-4 {{ $tdDeliveryColor }}" />
            @else
                <x-heroicon-o-building-storefront class="w-4 h-4 {{ $tdDeliveryColor }}" />
            @endif
        @endif
        <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
    </span>
    <span class="inline-flex items-center gap-1">
        @if(!empty($op->pickup_transport_mode))
            @if($op->pickup_transport_mode === 'Truck')
                <x-heroicon-o-truck class="w-4 h-4 {{ $tdPickupColor }}" />
            @else
                <x-heroicon-o-building-storefront class="w-4 h-4 {{ $tdPickupColor }}" />
            @endif
        @endif
        <span>{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
    </span>
</div>

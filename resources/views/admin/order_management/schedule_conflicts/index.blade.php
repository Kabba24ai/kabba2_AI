@extends('admin.layouts.app')

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-6 md:px-6 2xl:px-11">

    {{-- ── Page Header ────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600" />
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Schedule Conflicts</h1>
                <p class="text-sm text-gray-500">Equipment assigned to overlapping orders</p>
            </div>
        </div>

        @if($totalConflicts > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-700 border border-red-200">
                <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                {{ $totalConflicts }} {{ Str::plural('conflict', $totalConflicts) }} found
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-700 border border-green-200">
                <x-heroicon-o-check-circle class="w-4 h-4" />
                No conflicts found
            </span>
        @endif
    </div>

    {{-- ── Double Bookings ─────────────────────────────────────────────────── --}}
    <div class="mb-4 flex items-center gap-2">
        <x-heroicon-o-calendar-days class="w-5 h-5 text-orange-500" />
        <h2 class="text-base font-semibold text-gray-800">Double Bookings</h2>
        <span class="text-xs text-gray-400">(same equipment, overlapping rental dates)</span>
    </div>

    @if($totalConflicts === 0)
        <div class="text-center py-20 text-gray-400">
            <x-heroicon-o-check-badge class="mx-auto w-12 h-12 mb-3 opacity-40" />
            <p class="text-sm font-medium">All clear — no double bookings detected.</p>
            <p class="text-xs mt-1">The schedule is clean. No equipment is assigned to overlapping orders.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($doubleBookings as $conflict)
                @php
                    $equipment    = $conflict['equipment'];
                    $a            = $conflict['a'];
                    $b            = $conflict['b'];
                    $orderA       = $a->order;
                    $orderB       = $b->order;
                    $custA        = $orderA?->customer;
                    $custB        = $orderB?->customer;
                    $overlapStart = $conflict['overlap_start'];
                    $overlapEnd   = $conflict['overlap_end'];
                    $categoryId   = $equipment?->product_category_id;
                    $scheduleAssignUrl = route('admin.order-management.schedule-assignment.index')
                        . ($categoryId ? '?category=' . $categoryId : '');
                @endphp

                <div class="bg-white border border-red-200 rounded-xl shadow-sm overflow-hidden">

                    {{-- Equipment header bar --}}
                    <div class="flex items-center justify-between bg-red-50 border-b border-red-200 px-5 py-3">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-wrench-screwdriver class="w-5 h-5 text-red-500 shrink-0" />
                            <div>
                                <span class="font-semibold text-gray-900 text-sm">
                                    {{ $equipment?->equipment_name ?? 'Unknown Equipment' }}
                                </span>
                                @if($equipment?->equipment_id)
                                    <span class="ml-2 text-xs text-gray-500">ID: {{ $equipment->equipment_id }}</span>
                                @endif
                                @if($equipment?->serial_number)
                                    <span class="ml-2 text-xs text-gray-500">S/N: {{ $equipment->serial_number }}</span>
                                @endif
                                @if($equipment?->productCategory?->title)
                                    <span class="ml-2 text-xs text-gray-400">{{ $equipment->productCategory->title }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Resolve button → Schedule Assignment pre-filtered to this equipment category --}}
                            <a href="{{ $scheduleAssignUrl }}"
                               title="View all {{ $equipment?->productCategory?->title ?? 'equipment' }} in Schedule Assignment to find alternatives or adjust dates"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-orange-500 text-white hover:bg-orange-600 transition shadow-sm">
                                <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                Resolve Double Booking
                            </a>

                            <div class="flex items-center gap-2 text-xs font-medium text-red-700 bg-red-100 border border-red-200 rounded-full px-3 py-1">
                                <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                Overlap: {{ \App\Helpers\CustomHelper::formatDate($overlapStart) }}
                                @if(!$overlapStart->isSameDay($overlapEnd))
                                    – {{ \App\Helpers\CustomHelper::formatDate($overlapEnd) }}
                                @endif
                            </div>
                        </div>{{-- end right-side --}}
                    </div>{{-- end header bar --}}

                    {{-- Two conflicting orders side by side --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                        @foreach([['op' => $a, 'order' => $orderA, 'customer' => $custA], ['op' => $b, 'order' => $orderB, 'customer' => $custB]] as $idx => $side)
                        @php
                            $op       = $side['op'];
                            $order    = $side['order'];
                            $customer = $side['customer'];
                        @endphp
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">
                                            Order {{ $idx + 1 }}
                                        </span>
                                        @if($order)
                                            <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 text-sm font-bold text-blue-600 hover:underline">
                                                {{ $order->order_number }}
                                                <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                                            </a>
                                        @endif
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                        {{ $customer?->full_name ?? $order?->customer_name ?? '—' }}
                                    </p>
                                    @if($customer?->phone)
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ \App\Helpers\CustomHelper::formatPhone($customer->phone) }}
                                        </p>
                                    @endif
                                </div>

                                @if($op->product_name)
                                    <span class="text-xs text-gray-500 bg-gray-100 rounded-md px-2 py-1 shrink-0">
                                        {{ $op->product_name }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-3 text-xs">
                                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg
                                    @if($op->delivery_date && $overlapStart->lte(\Carbon\Carbon::parse($op->delivery_date)->endOfDay()) && $overlapEnd->gte(\Carbon\Carbon::parse($op->delivery_date)->startOfDay()))
                                        bg-red-50 text-red-700 border border-red-200
                                    @else
                                        bg-gray-50 text-gray-600 border border-gray-200
                                    @endif">
                                    <x-heroicon-o-truck class="w-3.5 h-3.5 shrink-0" />
                                    <span class="font-medium">Delivery:</span>
                                    {{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date) : '—' }}
                                    @if($op->delivery_time)
                                        <span class="text-gray-400">{{ $op->delivery_time }}</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg
                                    @if($op->pickup_date && $overlapStart->lte(\Carbon\Carbon::parse($op->pickup_date)->endOfDay()) && $overlapEnd->gte(\Carbon\Carbon::parse($op->pickup_date)->startOfDay()))
                                        bg-red-50 text-red-700 border border-red-200
                                    @else
                                        bg-gray-50 text-gray-600 border border-gray-200
                                    @endif">
                                    <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5 shrink-0" />
                                    <span class="font-medium">Pickup:</span>
                                    {{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date) : '—' }}
                                    @if($op->pickup_time)
                                        <span class="text-gray-400">{{ $op->pickup_time }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($op->delivery_status || $op->pickup_status)
                                <div class="flex gap-2 mt-2">
                                    @if($op->delivery_status)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $op->delivery_status === 'Completed' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200' }}">
                                            Delivery: {{ $op->delivery_status }}
                                        </span>
                                    @endif
                                    @if($op->pickup_status)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $op->pickup_status === 'Completed' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200' }}">
                                            Pickup: {{ $op->pickup_status }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @endforeach

                    </div>

                    {{-- Resolution footer --}}
                    <div class="bg-gray-50 border-t border-gray-100 px-5 py-3 flex items-center justify-between gap-4">
                        <p class="text-xs text-gray-500">
                            Resolve by editing one of the orders above to change its dates or reassign the equipment.
                        </p>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($orderA)
                                <a href="{{ route('admin.order-management.orders.edit', $orderA->unique_id) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                    Edit Order 1
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3" />
                                </a>
                            @endif
                            @if($orderA && $orderB)
                                <span class="text-gray-300">|</span>
                            @endif
                            @if($orderB)
                                <a href="{{ route('admin.order-management.orders.edit', $orderB->unique_id) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                    Edit Order 2
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3" />
                                </a>
                            @endif
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

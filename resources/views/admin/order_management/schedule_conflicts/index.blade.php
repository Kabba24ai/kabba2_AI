@extends('admin.layouts.app')

@push('css')
<style>
    .tw-tooltip::before {
        content: "";
        position: absolute;
        top: -6px;
        left: 16px;
        width: 10px;
        height: 10px;
        background: #fff;
        border-left: 1px solid rgb(229 231 235);
        border-top: 1px solid rgb(229 231 235);
        transform: rotate(45deg);
    }
</style>
@endpush

@section('content')

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
            Schedule Conflicts
        </h1>

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

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.order-management.schedule-conflicts.index') }}" class="mb-6">
        <div class="flex flex-wrap items-center gap-3">

            {{-- Clear --}}
            <a href="{{ route('admin.order-management.schedule-conflicts.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition shrink-0">
                <x-heroicon-o-x-mark class="w-4 h-4" />
                Clear
            </a>

            {{-- Search --}}
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search name, equipment ID, order…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring focus:border-blue-400 outline-none" />
            </div>

            {{-- Category --}}
            <select name="category" onchange="this.form.submit()"
                class="py-2 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white focus:ring focus:border-blue-400 outline-none min-w-[160px]">
                <option value="">All categories</option>
                @foreach($categories as $catId => $catTitle)
                    <option value="{{ $catId }}" @selected($category == $catId)>{{ $catTitle }}</option>
                @endforeach
            </select>

            {{-- Store --}}
            <select name="store" onchange="this.form.submit()"
                class="py-2 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white focus:ring focus:border-blue-400 outline-none min-w-[140px]">
                <option value="">All stores</option>
                @foreach($stores as $storeId => $storeName)
                    <option value="{{ $storeId }}" @selected($store == $storeId)>{{ $storeName }}</option>
                @endforeach
            </select>

            {{-- Section / Status --}}
            <select name="section" onchange="this.form.submit()"
                class="py-2 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white focus:ring focus:border-blue-400 outline-none min-w-[180px]">
                <option value="">All conflict types</option>
                <option value="double_bookings"      @selected($section === 'double_bookings')>Double Bookings</option>
                <option value="damaged"              @selected($section === 'damaged')>Damaged Equipment</option>
                <option value="overdue"              @selected($section === 'overdue')>Overdue Equipment</option>
                <option value="no_direct_assignment" @selected($section === 'no_direct_assignment')>No Direct Assignment</option>
            </select>

            {{-- Search submit --}}
            <button type="submit"
                class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition shrink-0">
                Search
            </button>

        </div>
    </form>

    @if($totalConflicts === 0)
        <div class="text-center py-20 text-gray-400">
            <x-heroicon-o-check-badge class="mx-auto w-12 h-12 mb-3 opacity-40" />
            <p class="text-sm font-medium">All clear — no schedule conflicts detected.</p>
            <p class="text-xs mt-1">No double bookings and no orders assigned to damaged equipment.</p>
        </div>
    @endif

    {{-- Double Bookings section --}}
    @if(count($doubleBookings) > 0)

    <div class="mb-4 flex items-center gap-2">
        <x-heroicon-o-calendar-days class="w-5 h-5 text-orange-500" />
        <h2 class="text-base font-semibold text-gray-800">Double Bookings</h2>
        <span class="text-xs text-gray-400">(same equipment, overlapping rental dates)</span>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm text-left whitespace-nowrap">
                <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                    <tr>
                        <th class="py-4 px-6 text-left">Product</th>
                        <th class="py-4 px-6 text-center">Order</th>
                        <th class="py-4 px-6 text-left">Customer</th>
                        <th class="py-4 px-6 text-left">Delivery Address</th>
                        <th class="py-4 px-6 text-left">Phone</th>
                        <th class="py-4 px-6 text-center">Equipment</th>
                        <th class="py-4 px-6 text-center">Equipment Id</th>
                        <th class="py-4 px-6 text-center">Location</th>
                        <th class="py-4 px-6 text-center">Delivery Date</th>
                        <th class="py-4 px-6 text-center">Return Date</th>
                        <th class="py-4 px-6 text-center">Payment</th>
                        <th class="py-4 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    @foreach($doubleBookings as $loopIndex => $conflict)
                        @php
                            $equipment    = $conflict['equipment'];
                            $overlapStart = $conflict['overlap_start'];
                            $overlapEnd   = $conflict['overlap_end'];
                            $categoryId   = $equipment?->product_category_id;
                            $scheduleAssignUrl = route('admin.order-management.schedule-assignment.index')
                                . ($categoryId ? '?category=' . $categoryId : '');
                            $primaryOpId = $conflict['a']->id;
                        @endphp

                        {{-- Conflict group header row --}}
                        <tr class="bg-yellow-50 border-y border-yellow-200">
                            <td colspan="12" class="px-6 py-2.5">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-red-500 shrink-0" />
                                        <span class="font-semibold text-gray-900 text-sm">
                                            {{ $equipment?->equipment_name ?? 'Unknown Equipment' }}
                                        </span>
                                        @if($equipment?->equipment_id)
                                            <span class="text-xs font-mono text-gray-600 bg-white border border-gray-200 rounded px-1.5 py-0.5">
                                                ID: {{ $equipment->equipment_id }}
                                            </span>
                                        @endif
                                        @if($equipment?->productCategory?->title)
                                            <span class="text-xs text-gray-400">{{ $equipment->productCategory->title }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700 bg-red-100 border border-red-200 rounded-full px-2.5 py-0.5">
                                            <x-heroicon-o-calendar class="w-3 h-3" />
                                            Overlap: {{ \App\Helpers\CustomHelper::formatDate($overlapStart) }}
                                            @if(!$overlapStart->isSameDay($overlapEnd))
                                                – {{ \App\Helpers\CustomHelper::formatDate($overlapEnd) }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button"
                                            class="ai-suggest-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white hover:bg-purple-700 transition shadow-sm"
                                            data-conflict-index="{{ $loopIndex }}"
                                            data-order-product-id="{{ $primaryOpId }}"
                                            data-equipment-name="{{ $equipment?->equipment_name }}">
                                            <x-heroicon-o-sparkles class="w-3.5 h-3.5" />
                                            Ai Suggest!
                                        </button>
                                        <a href="{{ $scheduleAssignUrl }}"
                                           title="View all {{ $equipment?->productCategory?->title ?? 'equipment' }} in Schedule Assignment"
                                           class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-orange-500 text-white hover:bg-orange-600 transition shadow-sm">
                                            <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                                            Resolve Double Booking
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Two conflicting order product rows --}}
                        @foreach([$conflict['a'], $conflict['b']] as $op)
                        @php
                            $order    = $op->order;
                            $customer = $order?->customer;
                            $preferredCategoryId = $op->product?->categories?->first()?->id
                                ?? $op->equipment?->product_category_id;
                            $deliveryIconColor = $op->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                            $pickupIconColor   = $op->pickup_status   === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <tr class="hover:bg-gray-50">

                            {{-- Product --}}
                            <td class="py-4 px-6 text-left min-w-[160px] max-w-[220px]">
                                {{ $op->product_name }}
                                @php
                                    $cats     = $op->product?->categories ?? collect();
                                    $catCount = $cats->count();
                                @endphp
                                @if($catCount === 1)
                                    <div class="text-xs text-gray-500 mt-1">{{ $cats->first()->title }}</div>
                                @elseif($catCount > 1)
                                    @php
                                        $tooltipHtml = '<div class="font-semibold mb-2">Categories:</div>';
                                        foreach ($cats as $cat) {
                                            $tooltipHtml .= '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                                        }
                                    @endphp
                                    <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                                          data-tooltip-html="{{ $tooltipHtml }}">
                                        Categories ({{ $catCount }})
                                    </span>
                                @endif
                            </td>

                            {{-- Order --}}
                            <td class="py-4 px-6 text-center">
                                {!! $order?->view_link ?? '-' !!}
                            </td>

                            {{-- Customer --}}
                            <td class="py-4 px-6 text-left">
                                <div class="font-medium">{{ $order?->customer_name ?? '-' }}</div>
                                @if($customer?->company_name)
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if(!empty($customer->company_website))
                                            <a href="{{ $customer->company_website }}" class="underline">{{ $customer->company_name }}</a>
                                        @else
                                            {{ $customer->company_name }}
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Delivery Address --}}
                            <td class="py-4 px-6 truncate min-w-[180px] max-w-[240px]">
                                {{ $order?->shippingAddress?->full_address ?? '-' }}
                            </td>

                            {{-- Phone --}}
                            <td class="py-4 px-6 text-left">
                                {{ $order?->shippingAddress?->phone ?? '-' }}
                            </td>

                            {{-- Equipment (assign modal trigger) --}}
                            <td class="py-4 px-6 text-center">
                                <button type="button"
                                    class="text-blue-600 underline equipment-assign-btn"
                                    data-order-product-unique-id="{{ $op->unique_id }}"
                                    data-order-unique-id="{{ $order?->unique_id }}"
                                    data-order-id="{{ $order?->order_number }}"
                                    data-customer-name="{{ $order?->customer_name }}"
                                    data-category-id="{{ $preferredCategoryId ?? '' }}"
                                    data-product-name="{{ $op->product_name }}">
                                    {{ $op->equipment?->equipment_name
                                        ?: ($op->softAssignment?->equipment?->equipment_name ?: 'Assign') }}
                                </button>
                            </td>

                            {{-- Equipment ID --}}
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                    {{ $op->equipment?->equipment_id
                                        ?? ($op->softAssignment?->equipment?->equipment_id ?? '-') }}
                                </span>
                            </td>

                            {{-- Location --}}
                            <td class="py-4 px-6 text-center">
                                {{ $op->equipment?->store?->store_name
                                    ?? ($op->softAssignment?->equipment?->store?->store_name ?? '-') }}
                            </td>

                            {{-- Delivery Date --}}
                            <td class="py-4 px-6 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center gap-1">
                                        @if(!empty($op->delivery_transport_mode))
                                            @if($op->delivery_transport_mode === 'Truck')
                                                <x-heroicon-o-truck class="w-4 h-4 {{ $deliveryIconColor }}" />
                                            @else
                                                <x-heroicon-o-building-storefront class="w-4 h-4 {{ $deliveryIconColor }}" />
                                            @endif
                                        @endif
                                        <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1">
                                        {{ $op->delivery_time ? \App\Helpers\CustomHelper::formatTime($op->delivery_time) : '' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Return Date --}}
                            <td class="py-4 px-6 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center gap-1">
                                        @if(!empty($op->pickup_transport_mode))
                                            @if($op->pickup_transport_mode === 'Truck')
                                                <x-heroicon-o-truck class="w-4 h-4 {{ $pickupIconColor }}" />
                                            @else
                                                <x-heroicon-o-building-storefront class="w-4 h-4 {{ $pickupIconColor }}" />
                                            @endif
                                        @endif
                                        <span>{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1">
                                        {{ $op->pickup_time ? \App\Helpers\CustomHelper::formatTime($op->pickup_time) : '' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Payment --}}
                            <td class="py-4 px-6 text-center">
                                {!! \App\Helpers\CustomHelper::statusBadge($order?->last_payment_status) !!}
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-6">
                                <div class="flex gap-2 items-center justify-center">
                                    <a href="{{ route('admin.order-management.orders.edit', $order?->unique_id ?? 0) }}"
                                       class="text-sky-600 hover:text-sky-800" title="Edit Order">
                                        @if($order?->notes?->isNotEmpty())
                                            <x-heroicon-o-book-open class="w-4 h-4" />
                                        @else
                                            <x-heroicon-o-eye class="w-4 h-4" />
                                        @endif
                                    </a>
                                </div>
                            </td>

                        </tr>
                        @endforeach

                    @endforeach

                </tbody>
            </table>
        </div>
    @endif

    {{-- ── Damaged Equipment section ──────────────────────────────────────────── --}}
    @if(count($damagedBookings) > 0)

    <div class="mt-8 mb-4 flex items-center gap-2">
        <x-heroicon-o-wrench-screwdriver class="w-5 h-5 text-red-500" />
        <h2 class="text-base font-semibold text-gray-800">Damaged Equipment</h2>
        <span class="text-xs text-gray-400">(active orders assigned to equipment with Damaged status)</span>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                <tr>
                    <th class="py-4 px-6 text-left">Product</th>
                    <th class="py-4 px-6 text-center">Order</th>
                    <th class="py-4 px-6 text-left">Customer</th>
                    <th class="py-4 px-6 text-left">Delivery Address</th>
                    <th class="py-4 px-6 text-left">Phone</th>
                    <th class="py-4 px-6 text-center">Equipment</th>
                    <th class="py-4 px-6 text-center">Equipment Id</th>
                    <th class="py-4 px-6 text-center">Location</th>
                    <th class="py-4 px-6 text-center">Delivery Date</th>
                    <th class="py-4 px-6 text-center">Return Date</th>
                    <th class="py-4 px-6 text-center">Payment</th>
                    <th class="py-4 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

            @foreach($damagedBookings as $dLoopIndex => $damage)
                @php
                    $dEquipment   = $damage['equipment'];
                    $resolveUrl   = $dEquipment?->unique_id
                        ? route('admin.maintenance-management.equipment.edit', $dEquipment->unique_id)
                        : '#';
                    $dPrimaryOpId = $damage['orders']->first()?->id;
                @endphp

                {{-- Damaged group header row --}}
                <tr class="bg-red-50 border-y border-red-200">
                    <td colspan="12" class="px-6 py-2.5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-500 text-white tracking-wide">DAMAGED</span>
                                <span class="font-semibold text-gray-900 text-sm">
                                    {{ $dEquipment?->equipment_name ?? 'Unknown Equipment' }}
                                </span>
                                @if($dEquipment?->equipment_id)
                                    <span class="text-xs font-mono text-gray-600 bg-white border border-gray-200 rounded px-1.5 py-0.5">
                                        ID: {{ $dEquipment->equipment_id }}
                                    </span>
                                @endif
                                @if($dEquipment?->productCategory?->title)
                                    <span class="text-xs text-gray-400">{{ $dEquipment->productCategory->title }}</span>
                                @endif
                                <span class="text-xs text-gray-500">Order Assigned to Damaged Equipment</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button"
                                    class="ai-suggest-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white hover:bg-purple-700 transition shadow-sm"
                                    data-conflict-index="d{{ $dLoopIndex }}"
                                    data-order-product-id="{{ $dPrimaryOpId }}"
                                    data-equipment-name="{{ $dEquipment?->equipment_name }}">
                                    <x-heroicon-o-sparkles class="w-3.5 h-3.5" />
                                    Ai Suggest!
                                </button>
                                <a href="{{ $resolveUrl }}"
                                   title="Update equipment status in Maintenance Management"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-red-600 text-white hover:bg-red-700 transition shadow-sm">
                                    <x-heroicon-o-wrench-screwdriver class="w-3.5 h-3.5" />
                                    Resolve Damaged Booking
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>

                {{-- Order rows for this damaged equipment --}}
                @foreach($damage['orders'] as $op)
                @php
                    $order    = $op->order;
                    $customer = $order?->customer;
                    $preferredCategoryId = $op->product?->categories?->first()?->id
                        ?? $op->equipment?->product_category_id
                        ?? $op->softAssignment?->equipment?->product_category_id;
                    $deliveryIconColor = $op->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                    $pickupIconColor   = $op->pickup_status   === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                @endphp
                <tr class="hover:bg-gray-50">

                    <td class="py-4 px-6 text-left min-w-[160px] max-w-[220px]">
                        {{ $op->product_name }}
                        @php
                            $cats     = $op->product?->categories ?? collect();
                            $catCount = $cats->count();
                        @endphp
                        @if($catCount === 1)
                            <div class="text-xs text-gray-500 mt-1">{{ $cats->first()->title }}</div>
                        @elseif($catCount > 1)
                            @php
                                $tooltipHtml = '<div class="font-semibold mb-2">Categories:</div>';
                                foreach ($cats as $cat) {
                                    $tooltipHtml .= '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                                }
                            @endphp
                            <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                                  data-tooltip-html="{{ $tooltipHtml }}">
                                Categories ({{ $catCount }})
                            </span>
                        @endif
                    </td>

                    <td class="py-4 px-6 text-center">{!! $order?->view_link ?? '-' !!}</td>

                    <td class="py-4 px-6 text-left">
                        <div class="font-medium">{{ $order?->customer_name ?? '-' }}</div>
                        @if($customer?->company_name)
                            <div class="text-xs text-gray-500 mt-1">
                                @if(!empty($customer->company_website))
                                    <a href="{{ $customer->company_website }}" class="underline">{{ $customer->company_name }}</a>
                                @else
                                    {{ $customer->company_name }}
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="py-4 px-6 truncate min-w-[180px] max-w-[240px]">
                        {{ $order?->shippingAddress?->full_address ?? '-' }}
                    </td>

                    <td class="py-4 px-6 text-left">{{ $order?->shippingAddress?->phone ?? '-' }}</td>

                    {{-- Equipment assign --}}
                    <td class="py-4 px-6 text-center">
                        <button type="button"
                            class="text-blue-600 underline equipment-assign-btn"
                            data-order-product-unique-id="{{ $op->unique_id }}"
                            data-order-unique-id="{{ $order?->unique_id }}"
                            data-order-id="{{ $order?->order_number }}"
                            data-customer-name="{{ $order?->customer_name }}"
                            data-category-id="{{ $preferredCategoryId ?? '' }}"
                            data-product-name="{{ $op->product_name }}">
                            {{ $op->equipment?->equipment_name ?: ($op->softAssignment?->equipment?->equipment_name ?: 'Assign') }}
                        </button>
                    </td>

                    {{-- Equipment ID + DAMAGED badge (always shown since this section is damaged) --}}
                    <td class="py-4 px-6 text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $op->equipment?->equipment_id ?? ($op->softAssignment?->equipment?->equipment_id ?? '-') }}
                        </span>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 border border-red-200 tracking-wide">DAMAGED</span>
                        </div>
                    </td>

                    <td class="py-4 px-6 text-center">
                        {{ $op->equipment?->store?->store_name ?? ($op->softAssignment?->equipment?->store?->store_name ?? '-') }}
                    </td>

                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(!empty($op->delivery_transport_mode))
                                    @if($op->delivery_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $deliveryIconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $deliveryIconColor }}" />
                                    @endif
                                @endif
                                <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $op->delivery_time ? \App\Helpers\CustomHelper::formatTime($op->delivery_time) : '' }}
                            </span>
                        </div>
                    </td>

                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(!empty($op->pickup_transport_mode))
                                    @if($op->pickup_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $pickupIconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $pickupIconColor }}" />
                                    @endif
                                @endif
                                <span>{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $op->pickup_time ? \App\Helpers\CustomHelper::formatTime($op->pickup_time) : '' }}
                            </span>
                        </div>
                    </td>

                    <td class="py-4 px-6 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($order?->last_payment_status) !!}
                    </td>

                    <td class="py-4 px-6">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order?->unique_id ?? 0) }}"
                               class="text-sky-600 hover:text-sky-800" title="Edit Order">
                                @if($order?->notes?->isNotEmpty())
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                @endif
                            </a>
                        </div>
                    </td>

                </tr>
                @endforeach

            @endforeach

            </tbody>
        </table>
    </div>

    @endif

    {{-- ── Overdue Equipment section ──────────────────────────────────────────────── --}}
    @if(count($overdueEquipmentConflicts) > 0)

    <div class="mt-8 mb-4 flex items-center gap-2">
        <x-heroicon-o-clock class="w-5 h-5 text-amber-500" />
        <h2 class="text-base font-semibold text-gray-800">Overdue Equipment</h2>
        <span class="text-xs text-gray-400">(equipment past return date with a new order due within 3 days)</span>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                <tr>
                    <th class="py-4 px-6 text-left">Product</th>
                    <th class="py-4 px-6 text-center">Order</th>
                    <th class="py-4 px-6 text-left">Customer</th>
                    <th class="py-4 px-6 text-left">Delivery Address</th>
                    <th class="py-4 px-6 text-left">Phone</th>
                    <th class="py-4 px-6 text-center">Equipment</th>
                    <th class="py-4 px-6 text-center">Equipment Id</th>
                    <th class="py-4 px-6 text-center">Location</th>
                    <th class="py-4 px-6 text-center">Delivery Date</th>
                    <th class="py-4 px-6 text-center">Return Date</th>
                    <th class="py-4 px-6 text-center">Payment</th>
                    <th class="py-4 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

            @foreach($overdueEquipmentConflicts as $ovdIndex => $ovdGroup)
                @php
                    $ovdEquipment = $ovdGroup['equipment'];
                    $daysOverdue  = \Carbon\Carbon::parse($ovdGroup['overdue_orders']->min('pickup_date'))->diffInDays(\Carbon\Carbon::today());
                @endphp

                {{-- Group header row --}}
                <tr class="bg-amber-50 border-y border-amber-200">
                    <td colspan="12" class="px-6 py-2.5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 flex-wrap">
                                <x-heroicon-o-clock class="w-4 h-4 text-amber-500 shrink-0" />
                                <span class="font-semibold text-gray-900 text-sm">
                                    {{ $ovdEquipment?->equipment_name ?? 'Unknown Equipment' }}
                                </span>
                                @if($ovdEquipment?->equipment_id)
                                    <span class="text-xs font-mono text-gray-600 bg-white border border-gray-200 rounded px-1.5 py-0.5">
                                        ID: {{ $ovdEquipment->equipment_id }}
                                    </span>
                                @endif
                                @if($ovdEquipment?->productCategory?->title)
                                    <span class="text-xs text-gray-400">{{ $ovdEquipment->productCategory->title }}</span>
                                @endif
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-100 border border-amber-200 rounded-full px-2.5 py-0.5">
                                    <x-heroicon-o-clock class="w-3 h-3" />
                                    {{ $daysOverdue }} {{ Str::plural('day', $daysOverdue) }} overdue
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700 bg-red-100 border border-red-200 rounded-full px-2.5 py-0.5">
                                    {{ $ovdGroup['upcoming_orders']->count() }} upcoming {{ Str::plural('order', $ovdGroup['upcoming_orders']->count()) }} within 3 days
                                </span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('admin.order-management.orders.edit', $ovdGroup['overdue_orders']->first()->order?->unique_id ?? 0) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600 transition shadow-sm">
                                    <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                                    View Overdue Order
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>

                {{-- OVERDUE order rows (past due, not returned) --}}
                @foreach($ovdGroup['overdue_orders'] as $op)
                @php
                    $order    = $op->order;
                    $customer = $order?->customer;
                    $deliveryIconColor = 'text-green-600';
                    $pickupIconColor   = 'text-red-500';
                @endphp
                <tr class="hover:bg-amber-50/40 bg-amber-25">
                    <td class="py-4 px-6 text-left min-w-[160px] max-w-[220px]">
                        {{ $op->product_name }}
                        @if($op->product?->categories?->count() === 1)
                            <div class="text-xs text-gray-500 mt-1">{{ $op->product->categories->first()->title }}</div>
                        @endif
                        <div class="mt-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200 tracking-wide">OVERDUE</span>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-center">{!! $order?->view_link ?? '-' !!}</td>
                    <td class="py-4 px-6 text-left">
                        <div class="font-medium">{{ $order?->customer_name ?? '-' }}</div>
                        @if($customer?->company_name)
                            <div class="text-xs text-gray-500 mt-1">{{ $customer->company_name }}</div>
                        @endif
                    </td>
                    <td class="py-4 px-6 truncate min-w-[180px] max-w-[240px]">{{ $order?->shippingAddress?->full_address ?? '-' }}</td>
                    <td class="py-4 px-6 text-left">{{ $order?->shippingAddress?->phone ?? '-' }}</td>
                    <td class="py-4 px-6 text-center">
                        <span class="text-gray-700">{{ $op->equipment?->equipment_name ?? '-' }}</span>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $op->equipment?->equipment_id ?? '-' }}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">{{ $op->equipment?->store?->store_name ?? '-' }}</td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-green-600" />
                                <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                <x-heroicon-o-exclamation-circle class="w-4 h-4 text-red-500" />
                                <span class="text-red-600 font-medium">{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-red-400 mt-1">Was due</span>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-center">{!! \App\Helpers\CustomHelper::statusBadge($order?->last_payment_status) !!}</td>
                    <td class="py-4 px-6">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order?->unique_id ?? 0) }}"
                               class="text-sky-600 hover:text-sky-800" title="Edit Order">
                                @if($order?->notes?->isNotEmpty())
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                @endif
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach

                {{-- UPCOMING order rows (at risk — delivery within 3 days) --}}
                @foreach($ovdGroup['upcoming_orders'] as $op)
                @php
                    $order    = $op->order;
                    $customer = $order?->customer;
                    $preferredCategoryId = $op->product?->categories?->first()?->id
                        ?? $op->equipment?->product_category_id
                        ?? $op->softAssignment?->equipment?->product_category_id;
                    $deliveryIconColor = 'text-yellow-600';
                    $pickupIconColor   = 'text-yellow-600';
                    $assignedEq = $op->equipment ?? $op->softAssignment?->equipment;
                @endphp
                <tr class="hover:bg-gray-50 border-t border-dashed border-amber-200">
                    <td class="py-4 px-6 text-left min-w-[160px] max-w-[220px]">
                        {{ $op->product_name }}
                        @if($op->product?->categories?->count() === 1)
                            <div class="text-xs text-gray-500 mt-1">{{ $op->product->categories->first()->title }}</div>
                        @endif
                        <div class="mt-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200 tracking-wide">UPCOMING</span>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-center">{!! $order?->view_link ?? '-' !!}</td>
                    <td class="py-4 px-6 text-left">
                        <div class="font-medium">{{ $order?->customer_name ?? '-' }}</div>
                        @if($customer?->company_name)
                            <div class="text-xs text-gray-500 mt-1">{{ $customer->company_name }}</div>
                        @endif
                    </td>
                    <td class="py-4 px-6 truncate min-w-[180px] max-w-[240px]">{{ $order?->shippingAddress?->full_address ?? '-' }}</td>
                    <td class="py-4 px-6 text-left">{{ $order?->shippingAddress?->phone ?? '-' }}</td>
                    <td class="py-4 px-6 text-center">
                        <button type="button"
                            class="text-blue-600 underline equipment-assign-btn"
                            data-order-product-unique-id="{{ $op->unique_id }}"
                            data-order-unique-id="{{ $order?->unique_id }}"
                            data-order-id="{{ $order?->order_number }}"
                            data-customer-name="{{ $order?->customer_name }}"
                            data-category-id="{{ $preferredCategoryId ?? '' }}"
                            data-product-name="{{ $op->product_name }}">
                            {{ $assignedEq?->equipment_name ?: 'Assign' }}
                        </button>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $assignedEq?->equipment_id ?? '-' }}
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center">{{ $assignedEq?->store?->store_name ?? '-' }}</td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(!empty($op->delivery_transport_mode))
                                    @if($op->delivery_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $deliveryIconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $deliveryIconColor }}" />
                                    @endif
                                @endif
                                <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $op->delivery_time ? \App\Helpers\CustomHelper::formatTime($op->delivery_time) : '' }}
                            </span>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(!empty($op->pickup_transport_mode))
                                    @if($op->pickup_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $pickupIconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $pickupIconColor }}" />
                                    @endif
                                @endif
                                <span>{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $op->pickup_time ? \App\Helpers\CustomHelper::formatTime($op->pickup_time) : '' }}
                            </span>
                        </div>
                    </td>
                    <td class="py-4 px-6 text-center">{!! \App\Helpers\CustomHelper::statusBadge($order?->last_payment_status) !!}</td>
                    <td class="py-4 px-6">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order?->unique_id ?? 0) }}"
                               class="text-sky-600 hover:text-sky-800" title="Edit Order">
                                @if($order?->notes?->isNotEmpty())
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                @endif
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach

            @endforeach

            </tbody>
        </table>
    </div>

    @endif

    {{-- ── No Direct Assignment section ─────────────────────────────────────────── --}}
    @if(count($noDirectAssignmentGroups) > 0)

    <div class="mt-8 mb-4 flex items-center gap-2">
        <x-heroicon-o-link-slash class="w-5 h-5 text-orange-500" />
        <h2 class="text-base font-semibold text-gray-800">No Direct Assignment Defined</h2>
        <span class="text-xs text-gray-400">(products with no equipment configured as a direct assignment)</span>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
                <tr>
                    <th class="py-4 px-6 text-left">Product</th>
                    <th class="py-4 px-6 text-center">Order</th>
                    <th class="py-4 px-6 text-left">Customer</th>
                    <th class="py-4 px-6 text-left">Delivery Address</th>
                    <th class="py-4 px-6 text-left">Phone</th>
                    <th class="py-4 px-6 text-center">Equipment</th>
                    <th class="py-4 px-6 text-center">Equipment Id</th>
                    <th class="py-4 px-6 text-center">Location</th>
                    <th class="py-4 px-6 text-center">Delivery Date</th>
                    <th class="py-4 px-6 text-center">Return Date</th>
                    <th class="py-4 px-6 text-center">Payment</th>
                    <th class="py-4 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

            @foreach($noDirectAssignmentGroups as $ndaIndex => $group)

                {{-- Group header row --}}
                <tr class="bg-orange-50 border-y border-orange-200">
                    <td colspan="12" class="px-6 py-2.5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 flex-wrap">
                                <x-heroicon-o-link-slash class="w-4 h-4 text-orange-500 shrink-0" />
                                <span class="font-semibold text-gray-900 text-sm">
                                    {{ $group['product_name'] }}
                                </span>
                                @if($group['product']?->categories?->isNotEmpty())
                                    <span class="text-xs text-gray-400">{{ $group['product']->categories->pluck('title')->join(', ') }}</span>
                                @endif
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-orange-700 bg-orange-100 border border-orange-200 rounded-full px-2.5 py-0.5">
                                    No Direct Assignment Equipment Configured
                                </span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @php
                                    $ndaCategoryId = $group['product']?->categories?->first()?->id;
                                    $worksheetUrl  = route('admin.maintenance-management.equipment-worksheet.index')
                                        . ($ndaCategoryId ? '?category=' . $ndaCategoryId : '');
                                @endphp
                                <a href="{{ $worksheetUrl }}"
                                   title="Go to Equipment Worksheet — configure Direct Assignment for this product category"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-orange-500 text-white hover:bg-orange-600 transition shadow-sm">
                                    <x-heroicon-o-wrench-screwdriver class="w-3.5 h-3.5" />
                                    Configure Direct Assignment
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>

                {{-- Order rows for this product group --}}
                @foreach($group['orders'] as $op)
                @php
                    $order    = $op->order;
                    $customer = $order?->customer;
                    $preferredCategoryId = $op->product?->categories?->first()?->id;
                    $deliveryIconColor = $op->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                    $pickupIconColor   = $op->pickup_status   === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                @endphp
                <tr class="hover:bg-gray-50">

                    <td class="py-4 px-6 text-left min-w-[160px] max-w-[220px]">
                        {{ $op->product_name }}
                        @php
                            $cats     = $op->product?->categories ?? collect();
                            $catCount = $cats->count();
                        @endphp
                        @if($catCount === 1)
                            <div class="text-xs text-gray-500 mt-1">{{ $cats->first()->title }}</div>
                        @elseif($catCount > 1)
                            @php
                                $tooltipHtml = '<div class="font-semibold mb-2">Categories:</div>';
                                foreach ($cats as $cat) {
                                    $tooltipHtml .= '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                                }
                            @endphp
                            <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                                  data-tooltip-html="{{ $tooltipHtml }}">
                                Categories ({{ $catCount }})
                            </span>
                        @endif
                    </td>

                    <td class="py-4 px-6 text-center">{!! $order?->view_link ?? '-' !!}</td>

                    <td class="py-4 px-6 text-left">
                        <div class="font-medium">{{ $order?->customer_name ?? '-' }}</div>
                        @if($customer?->company_name)
                            <div class="text-xs text-gray-500 mt-1">
                                @if(!empty($customer->company_website))
                                    <a href="{{ $customer->company_website }}" class="underline">{{ $customer->company_name }}</a>
                                @else
                                    {{ $customer->company_name }}
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="py-4 px-6 truncate min-w-[180px] max-w-[240px]">
                        {{ $order?->shippingAddress?->full_address ?? '-' }}
                    </td>

                    <td class="py-4 px-6 text-left">{{ $order?->shippingAddress?->phone ?? '-' }}</td>

                    {{-- Equipment assign --}}
                    <td class="py-4 px-6 text-center">
                        <button type="button"
                            class="text-blue-600 underline equipment-assign-btn"
                            data-order-product-unique-id="{{ $op->unique_id }}"
                            data-order-unique-id="{{ $order?->unique_id }}"
                            data-order-id="{{ $order?->order_number }}"
                            data-customer-name="{{ $order?->customer_name }}"
                            data-category-id="{{ $preferredCategoryId ?? '' }}"
                            data-product-name="{{ $op->product_name }}">
                            {{ $op->softAssignment?->equipment?->equipment_name ?: 'Assign' }}
                        </button>
                    </td>

                    <td class="py-4 px-6 text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $op->softAssignment?->equipment?->equipment_id ?? '-' }}
                        </span>
                    </td>

                    <td class="py-4 px-6 text-center">
                        {{ $op->softAssignment?->equipment?->store?->store_name ?? '-' }}
                    </td>

                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(!empty($op->delivery_transport_mode))
                                    @if($op->delivery_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $deliveryIconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $deliveryIconColor }}" />
                                    @endif
                                @endif
                                <span>{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $op->delivery_time ? \App\Helpers\CustomHelper::formatTime($op->delivery_time) : '' }}
                            </span>
                        </div>
                    </td>

                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if(!empty($op->pickup_transport_mode))
                                    @if($op->pickup_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $pickupIconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $pickupIconColor }}" />
                                    @endif
                                @endif
                                <span>{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date, 'M d, y') : 'N/A' }}</span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $op->pickup_time ? \App\Helpers\CustomHelper::formatTime($op->pickup_time) : '' }}
                            </span>
                        </div>
                    </td>

                    <td class="py-4 px-6 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($order?->last_payment_status) !!}
                    </td>

                    <td class="py-4 px-6">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order?->unique_id ?? 0) }}"
                               class="text-sky-600 hover:text-sky-800" title="Edit Order">
                                @if($order?->notes?->isNotEmpty())
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                @endif
                            </a>
                        </div>
                    </td>

                </tr>
                @endforeach

            @endforeach

            </tbody>
        </table>
    </div>

    @endif

{{-- ── AI Equipment Suggestion Modal ──────────────────────────────────── --}}
<div id="aiSuggestModal" class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 flex justify-center items-start px-4 py-10">
    <div class="bg-white rounded-xl w-full max-w-3xl shadow-2xl flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100">
                    <x-heroicon-o-sparkles class="h-4 w-4 text-purple-600" />
                </span>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">AI Equipment Suggestion</h2>
                    <p id="aiSuggestProductName" class="text-xs text-gray-500 mt-0.5"></p>
                </div>
            </div>
            <button type="button" id="closeAiSuggestModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        {{-- User select --}}
        <div class="px-6 pt-4 pb-2">
            <label class="block text-xs font-medium text-gray-700 mb-1">Assign As (Employee)</label>
            <select id="aiSuggestUserSelect"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white text-gray-700 focus:ring focus:border-purple-400">
                <option value="">Select Employee</option>
                @foreach ($employees as $employeeId => $employeeName)
                    <option value="{{ $employeeId }}">{{ $employeeName }}</option>
                @endforeach
            </select>
        </div>

        {{-- Body --}}
        <div id="aiSuggestBody" class="px-6 py-4 overflow-y-auto max-h-[65vh] space-y-3">
            <p class="text-sm text-gray-400">Loading suggestions…</p>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
            <button type="button" id="closeAiSuggestModalFooter"
                class="px-5 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-100">
                Close
            </button>
        </div>
    </div>
</div>
{{-- ── End AI Equipment Suggestion Modal ───────────────────────────────── --}}

{{-- Equipment Assign Modal (same as Schedule page) --}}
<div id="equipmentAssignModal"
    class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
    <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
        <div class="relative px-6 pt-6 pb-4 border-b">
            <h2 class="text-xl font-semibold text-gray-900 text-center">Assign Equipment</h2>
            <button type="button"
                class="close-equipment-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none absolute right-6 top-6">&times;</button>
        </div>

        {{ html()->form()->attributes(['data-parsley-validate' => true, 'class' => 'flex-1', 'id' => 'equipmentAssignForm'])->open() }}

        <div class="px-4 pt-3 space-y-2 overflow-y-auto">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
                <div>
                    <span class="text-xs font-semibold text-blue-700">Order ID:</span>
                    <a id="assign-order-id" href="#" class="text-sm text-blue-900 font-semibold">-</a>
                </div>
                <div>
                    <span class="text-xs font-semibold text-blue-700">Customer:</span>
                    <span id="assign-customer-name" class="text-sm text-blue-900 font-semibold">-</span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-blue-700">Product Ordered:</span>
                    <span id="assign-product-name" class="text-sm text-blue-900 font-semibold">-</span>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 required" for="user_unique_id">User</label>
                <select name="user_unique_id" id="user_unique_id"
                    class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700"
                    required>
                    <option value="">Select Employee</option>
                    @foreach ($employees as $employeeId => $employeeName)
                        <option value="{{ $employeeId }}">{{ $employeeName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 required">Category</label>
                <select id="category_select"
                    class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-700">
                    <option value="">Select Category</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 required" for="equipment_unique_id">Equipment</label>
                <select name="equipment_unique_id" id="equipment_unique_id"
                    class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                    <option value="" data-current-status="">Select Equipment</option>
                </select>
                <div class="flex items-center justify-between gap-3 mt-2">
                    <span id="equipment-status-display" class="text-sm font-semibold text-yellow-400"></span>
                    <a href="#" class="text-blue-600 hover:underline text-sm font-semibold" id="equipment-page-link"></a>
                </div>
            </div>
            <input type="hidden" id="order-product-unique-id" name="order_product_unique_id" value="">
        </div>

        <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
            <button type="button"
                class="close-equipment-assign-modal px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                Cancel
            </button>
            <button type="submit" id="equipment-assign-submit"
                class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Assign
            </button>
        </div>
        </form>
    </div>
</div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Tooltip ──────────────────────────────────────────────────────────────
    if (!window.__scheduleTooltipInitialized) {
        window.__scheduleTooltipInitialized = true;
        const tooltip = document.createElement('div');
        tooltip.className = 'fixed hidden rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 shadow-lg max-w-xs z-[9999]';
        document.body.appendChild(tooltip);
        let activeTrigger = null;
        let timeouts = { open: null, close: null };
        const DELAYS = { open: 120, close: 80 };
        const GAP = 8;
        function hide() { clearTimeout(timeouts.open); clearTimeout(timeouts.close); activeTrigger = null; tooltip.classList.add('hidden'); }
        function position(trigger) {
            if (!trigger) return;
            tooltip.classList.remove('hidden');
            const tr = trigger.getBoundingClientRect();
            const tp = tooltip.getBoundingClientRect();
            let top = tr.bottom + GAP, left = tr.left;
            if (left + tp.width > window.innerWidth - 10) left = window.innerWidth - tp.width - 10;
            if (left < 10) left = 10;
            if (top + tp.height > window.innerHeight - 10) top = tr.top - GAP - tp.height;
            tooltip.style.cssText = `top: ${top}px; left: ${left}px;`;
        }
        function show(trigger) { clearTimeout(timeouts.close); timeouts.open = setTimeout(() => { activeTrigger = trigger; tooltip.innerHTML = trigger.dataset.tooltipHtml || ''; requestAnimationFrame(() => position(trigger)); }, DELAYS.open); }
        function scheduleHide(trigger) { clearTimeout(timeouts.open); timeouts.close = setTimeout(() => { if (activeTrigger === trigger) hide(); }, DELAYS.close); }
        function getTrigger(t) { return t?.closest?.('.tooltip-trigger'); }
        document.addEventListener('pointerover', e => { const t = getTrigger(e.target); if (!t || t.contains(e.relatedTarget)) return; show(t); });
        document.addEventListener('pointerout',  e => { const t = getTrigger(e.target); if (!t || t.contains(e.relatedTarget)) return; scheduleHide(t); });
        ['scroll','resize'].forEach(ev => window.addEventListener(ev, () => { if (activeTrigger && !tooltip.classList.contains('hidden')) requestAnimationFrame(() => position(activeTrigger)); }, { passive: true }));
    }

    // ── AI Equipment Suggestion ──────────────────────────────────────────────
    (function () {
        const modal       = document.getElementById('aiSuggestModal');
        const body        = document.getElementById('aiSuggestBody');
        const productName = document.getElementById('aiSuggestProductName');
        const userSelect  = document.getElementById('aiSuggestUserSelect');
        const aiSuggestUrl = '{{ route("admin.order-management.schedule-assignment.ai-suggest", ["orderProductId" => "__OP_ID__"]) }}';
        const assignUrl    = '{{ route("admin.order-management.schedules.assign-equipment") }}';

        let pendingOrderProductUniqueId = '';

        function escapeHtml(v) {
            return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        }

        function openModal(orderProductId, equipmentName) {
            pendingOrderProductUniqueId = '';
            productName.textContent = equipmentName || '';
            body.innerHTML = '<p class="text-sm text-gray-400 py-4 text-center">Loading suggestions…</p>';
            modal.classList.remove('hidden');

            const url = aiSuggestUrl.replace('__OP_ID__', orderProductId);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        body.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">${escapeHtml(data.message || 'Failed to load suggestions.')}</div>`;
                        return;
                    }

                    pendingOrderProductUniqueId = data.order_product.unique_id;

                    const keys    = data.comparison_keys || [];
                    const suggs   = data.suggestions || [];
                    const total   = data.total_candidates ?? 0;
                    const summary = data.overall_summary || '';

                    if (suggs.length === 0) {
                        body.innerHTML = '<p class="text-sm text-gray-500 py-4 text-center">No suitable equipment found for this order product.</p>';
                        return;
                    }

                    const summaryHtml = summary
                        ? `<div class="mb-4 rounded-md border border-purple-100 bg-purple-50 px-3 py-2.5 text-sm text-purple-800">${escapeHtml(summary)}</div>`
                        : '';

                    const keyChips = keys.map(k => {
                        const color = { critical: 'bg-red-100 text-red-700', high: 'bg-orange-100 text-orange-700', medium: 'bg-blue-100 text-blue-700', low: 'bg-gray-100 text-gray-500' }[k.importance] || 'bg-gray-100 text-gray-500';
                        return `<span class="rounded-full px-2 py-0.5 text-[11px] font-medium ${color}">${escapeHtml(k.label)}</span>`;
                    }).join('');

                    const keysHtml = keys.length
                        ? `<div class="mb-4 rounded-md border border-gray-100 bg-gray-50 px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Comparison Criteria</p>
                            <div class="flex flex-wrap gap-1.5">${keyChips}</div>
                           </div>`
                        : '';

                    const recTypeLabel = { best_match: 'Best Match', suitable: 'Suitable', conditional: 'Conditional', not_recommended: 'Not Recommended' };
                    const recTypeColor = { best_match: 'bg-green-100 text-green-700', suitable: 'bg-blue-100 text-blue-700', conditional: 'bg-amber-100 text-amber-700', not_recommended: 'bg-red-100 text-red-600' };

                    const cards = suggs.map((s, i) => {
                        const statusColor = { Available: 'bg-green-100 text-green-700', Maintenance: 'bg-yellow-100 text-yellow-700', Rented: 'bg-blue-100 text-blue-700' }[s.status] || 'bg-gray-100 text-gray-500';
                        const topPick = i === 0 ? '<span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-700">Top Pick</span>' : '';
                        const border  = i === 0 ? 'border-purple-200 bg-purple-50/30' : 'border-gray-200';

                        const recType  = s.recommendation_type || '';
                        const recBadge = recType
                            ? `<span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ${recTypeColor[recType] || 'bg-gray-100 text-gray-500'}">${recTypeLabel[recType] || recType}</span>`
                            : '';

                        const score = typeof s.suitability_score === 'number'
                            ? `<div class="flex items-center gap-1 text-xs text-gray-500"><span class="font-semibold text-gray-700">${s.suitability_score}</span><span>/100</span></div>`
                            : '';

                        const matchedChips = (s.matched_specs || []).map(mk => {
                            const impColor = { critical: 'bg-red-50 text-red-600 border-red-200', high: 'bg-orange-50 text-orange-600 border-orange-200', medium: 'bg-blue-50 text-blue-600 border-blue-200', low: 'bg-gray-50 text-gray-500 border-gray-200' }[mk.importance] || 'bg-gray-50 text-gray-500 border-gray-200';
                            return `<span class="inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[11px] ${impColor}">
                                ${escapeHtml(mk.label)}: <strong>${escapeHtml(mk.value ?? '—')}${mk.unit ? ' ' + escapeHtml(mk.unit) : ''}</strong>
                                <span class="opacity-60">${mk.confidence_pct ?? 0}%</span>
                            </span>`;
                        }).join('');

                        const reasoningHtml = s.reasoning ? `<p class="mt-2 text-xs text-gray-600">${escapeHtml(s.reasoning)}</p>` : '';
                        const actionsHtml   = (s.actions_required || []).length ? `<div class="mt-2 flex flex-wrap gap-1">${s.actions_required.map(a => `<span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] text-blue-700">${escapeHtml(a)}</span>`).join('')}</div>` : '';
                        const warningsHtml  = (s.warnings || []).length ? `<div class="mt-2 flex flex-wrap gap-1">${s.warnings.map(w => `<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] text-amber-700">⚠ ${escapeHtml(w)}</span>`).join('')}</div>` : '';
                        const noProfile     = !s.has_ai_profile ? '<p class="text-[11px] text-gray-400 italic mt-1">No AI profile linked.</p>' : '';

                        const assignedBtn = s.is_currently_assigned
                            ? '<span class="text-xs text-green-600 font-medium">Currently Assigned</span>'
                            : `<button type="button"
                                    class="ai-assign-btn inline-flex items-center gap-1.5 rounded-md bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700 disabled:opacity-50"
                                    data-equipment-unique-id="${escapeHtml(s.equipment_unique_id)}"
                                    data-equipment-name="${escapeHtml(s.equipment_name)}">
                                    Assign
                               </button>`;

                        return `<div class="rounded-lg border ${border} p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-gray-900">${escapeHtml(s.equipment_name)}</span>
                                        ${topPick}${recBadge}
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium ${statusColor}">${escapeHtml(s.status_label || s.status)}</span>
                                        ${s.is_currently_assigned ? '<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Assigned</span>' : ''}
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500">#${escapeHtml(s.equipment_number ?? '—')} &nbsp;·&nbsp; ${escapeHtml(s.store ?? '—')}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    ${score}
                                    <span class="text-xs text-gray-400">${s.matched_spec_count}/${s.total_comparison_keys} criteria</span>
                                    ${assignedBtn}
                                </div>
                            </div>
                            ${matchedChips ? `<div class="mt-2.5 flex flex-wrap gap-1.5">${matchedChips}</div>` : ''}
                            ${reasoningHtml}${actionsHtml}${warningsHtml}${noProfile}
                        </div>`;
                    }).join('');

                    body.innerHTML = summaryHtml + keysHtml + cards + `<p class="text-center text-xs text-gray-400 pt-2">${total} candidate${total !== 1 ? 's' : ''} evaluated.</p>`;
                })
                .catch(() => {
                    body.innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">Failed to load suggestions. Please try again.</div>';
                });
        }

        function closeModal() {
            modal.classList.add('hidden');
            pendingOrderProductUniqueId = '';
        }

        document.getElementById('closeAiSuggestModal').addEventListener('click', closeModal);
        document.getElementById('closeAiSuggestModalFooter').addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        // Open on AI button click
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.ai-suggest-btn');
            if (!btn) return;
            openModal(btn.dataset.orderProductId, btn.dataset.equipmentName || btn.dataset.productName);
        });

        // Assign from suggestion card — on success, reload page so conflict list updates
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.ai-assign-btn');
            if (!btn) return;

            const userUniqueId = userSelect.value;
            if (!userUniqueId) { notyf.error('Please select an employee before assigning.'); return; }
            if (!pendingOrderProductUniqueId) { notyf.error('Order product reference lost. Please close and reopen.'); return; }

            const equipmentUniqueId = btn.dataset.equipmentUniqueId;
            const equipmentName     = btn.dataset.equipmentName;

            btn.disabled = true;
            btn.textContent = 'Assigning…';

            const params = new URLSearchParams({
                order_product_unique_id: pendingOrderProductUniqueId,
                equipment_unique_id:     equipmentUniqueId,
                user_unique_id:          userUniqueId,
            });

            fetch(assignUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Content-Type':     'application/x-www-form-urlencoded',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: params.toString(),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notyf.success(`Assigned: ${equipmentName}`);
                    closeModal();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    notyf.error(data.message || 'Assignment failed.');
                    btn.disabled = false;
                    btn.textContent = 'Assign';
                }
            })
            .catch(() => {
                notyf.error('Request failed. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Assign';
            });
        });
    })();
    // ── End AI Equipment Suggestion ──────────────────────────────────────────

    // ── Equipment Assign Modal ───────────────────────────────────────────────
    const modal               = document.getElementById('equipmentAssignModal');
    const equipmentAssignForm = document.getElementById('equipmentAssignForm');
    const categorySelect      = document.getElementById('category_select');
    const equipmentSelect     = document.getElementById('equipment_unique_id');
    const assignBtn           = document.getElementById('equipment-assign-submit');
    const orderIdLabel        = document.getElementById('assign-order-id');
    const customerNameLabel   = document.getElementById('assign-customer-name');
    const productNameLabel    = document.getElementById('assign-product-name');
    let pendingPreferredCategoryId = '';
    let fullData = {};

    function applyPreferredCategory(categoryId) {
        const id = String(categoryId || '').trim();
        if (!id || !categorySelect) { pendingPreferredCategoryId = ''; return; }
        const hasOption = Array.from(categorySelect.options).some(o => o.value === id);
        if (!hasOption) { pendingPreferredCategoryId = id; return; }
        pendingPreferredCategoryId = '';
        categorySelect.value = id;
        categorySelect.dispatchEvent(new Event('change'));
    }

    function updateEquipmentStatus() {
        const statusDiv = document.getElementById('equipment-status-display');
        const pageLink  = document.getElementById('equipment-page-link');
        if (!equipmentSelect || !statusDiv || !assignBtn || !pageLink) return;
        const sel    = equipmentSelect.options[equipmentSelect.selectedIndex] || {};
        const status = sel.getAttribute?.('data-current-status');
        const link   = sel.getAttribute?.('data-link') || '';
        const title  = sel.getAttribute?.('data-link-title') || '';
        if (!status) { statusDiv.textContent = ''; statusDiv.className = 'text-sm font-semibold text-gray-600'; assignBtn.disabled = true; pageLink.href = ''; pageLink.textContent = ''; return; }
        const map = { available: ['Available','text-green-600'], rented: ['Rented','text-gray-600'], damaged: ['Not Available','text-red-600'], maintenance: ['Maint. Hold','text-yellow-600'] };
        const [text, color] = map[status] || [status, 'text-gray-600'];
        statusDiv.textContent = `Status: ${text}`;
        statusDiv.className   = `text-sm font-semibold ${color}`;
        assignBtn.disabled    = false;
        pageLink.href         = link;
        pageLink.textContent  = title;
    }

    function appendGroup(label, list) {
        if (!list.length) return;
        const group = document.createElement('optgroup');
        group.label = label;
        list.forEach(eq => {
            const opt = document.createElement('option');
            opt.value = eq.unique_id;
            opt.textContent = eq.equipment_name + ' || ' + eq.equipment_id;
            opt.setAttribute('data-current-status', eq.current_status || '');
            opt.setAttribute('data-link', eq.link || '');
            opt.setAttribute('data-link-title', eq.link_title || '');
            group.appendChild(opt);
        });
        equipmentSelect.appendChild(group);
    }

    function loadEquipmentList(equipments) {
        equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';
        const groups = { available: [], rented: [], damaged: [], maintenance: [], other: [] };
        equipments.forEach(eq => {
            const s = (eq.current_status || '').toLowerCase();
            (groups[s] || groups.other).push(eq);
        });
        appendGroup('Available',   groups.available);
        appendGroup('Maint. Hold', groups.maintenance);
        appendGroup('Damaged',     groups.damaged);
        appendGroup('Rented',      groups.rented);
        appendGroup('Other',       groups.other);
        updateEquipmentStatus();
    }

    categorySelect.addEventListener('change', function () {
        const id = Number(this.value);
        let equipments = [];
        if (!id) {
            fullData.forEach(cat => { if (Array.isArray(cat.equipments)) equipments = equipments.concat(cat.equipments); });
        } else {
            const cat = fullData.find(c => c.id === id);
            equipments = cat?.equipments || [];
        }
        loadEquipmentList(equipments);
    });

    if (equipmentSelect) equipmentSelect.addEventListener('change', updateEquipmentStatus);

    function clearModalFields() {
        const userSel  = document.getElementById('user_unique_id');
        const orderInput = document.getElementById('order-product-unique-id');
        const statusDiv  = document.getElementById('equipment-status-display');
        const pageLink   = document.getElementById('equipment-page-link');
        if (userSel)     userSel.selectedIndex = 0;
        if (equipmentSelect) equipmentSelect.selectedIndex = 0;
        if (orderInput)  orderInput.value = '';
        if (orderIdLabel) { orderIdLabel.textContent = '-'; orderIdLabel.href = ''; }
        if (customerNameLabel) customerNameLabel.textContent = '-';
        if (productNameLabel)  productNameLabel.textContent  = '-';
        if (statusDiv)   { statusDiv.textContent = ''; statusDiv.className = 'text-sm font-semibold text-gray-600'; }
        if (pageLink)    { pageLink.href = ''; pageLink.textContent = ''; }
        if (equipmentAssignForm) equipmentAssignForm.reset();
        pendingPreferredCategoryId = '';
        if (assignBtn) { assignBtn.disabled = true; assignBtn.textContent = 'Assign'; }
    }

    // Open modal via event delegation (survives dynamic content)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.equipment-assign-btn');
        if (!btn) return;
        const orderProductUniqueId = btn.getAttribute('data-order-product-unique-id');
        const orderId        = btn.dataset.orderId || '';
        const orderUniqueId  = btn.dataset.orderUniqueId || '';
        const productName    = btn.dataset.productName || '';
        const customerName   = btn.dataset.customerName || '';
        const preferredCatId = btn.dataset.categoryId || '';
        const orderDetailUrl = "{{ route('admin.order-management.orders.edit', ['unique_id' => 'ORDER_ID_PLACEHOLDER']) }}";
        document.getElementById('order-product-unique-id').value = orderProductUniqueId || '';
        if (orderIdLabel) { orderIdLabel.textContent = orderId ? `#${orderId.replace(/^#/, '')}` : '-'; orderIdLabel.href = orderUniqueId ? orderDetailUrl.replace('ORDER_ID_PLACEHOLDER', orderUniqueId) : ''; }
        if (customerNameLabel) customerNameLabel.textContent = customerName || '-';
        if (productNameLabel)  productNameLabel.textContent  = productName  || '-';
        modal.classList.remove('hidden');
        applyPreferredCategory(preferredCatId);
        updateEquipmentStatus();
    });

    // Close modal
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.close-equipment-assign-modal')) return;
        clearModalFields();
        modal.classList.add('hidden');
    });

    // Form submit — reload page on success so conflict list updates
    equipmentAssignForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (window.$ && $(equipmentAssignForm).parsley && !$(equipmentAssignForm).parsley().isValid()) {
            $(equipmentAssignForm).parsley().validate();
            return;
        }
        if (assignBtn) { assignBtn.disabled = true; assignBtn.textContent = 'Assigning...'; }
        const formData = new FormData(equipmentAssignForm);
        apiFetch('{{ route('admin.order-management.schedules.assign-equipment') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'), 'Accept': 'application/json' },
            body: formData
        })
        .then(data => {
            if (data?.success) {
                modal.classList.add('hidden');
                if (window.notyf) notyf.success(data.message);
                clearModalFields();
                // Reload so conflict list reflects the new assignment
                setTimeout(() => window.location.reload(), 800);
            } else {
                if (window.notyf) notyf.error(data?.message || 'Something went wrong.');
                if (assignBtn) { assignBtn.disabled = false; assignBtn.textContent = 'Assign'; }
            }
        })
        .catch(() => {
            if (window.notyf) notyf.error('Request failed.');
            if (assignBtn) { assignBtn.disabled = false; assignBtn.textContent = 'Assign'; }
        });
    });

    // Fetch equipment categories + equipment for modal dropdowns
    apiFetch('{{ route('admin.maintenance-management.equipment.fetch-with-categories') }}')
        .then(data => {
            if (!data?.success) return;
            fullData = data.categories;
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            data.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.title;
                categorySelect.appendChild(opt);
            });
            // Pre-load all equipment
            let all = [];
            fullData.forEach(cat => { if (Array.isArray(cat.equipments)) all = all.concat(cat.equipments); });
            loadEquipmentList(all);
            if (pendingPreferredCategoryId) applyPreferredCategory(pendingPreferredCategoryId);
        });
});
</script>
@endpush

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

                        {{-- AI Suggestions panel — hidden until "Ai Suggest!" is clicked --}}
                        <tr id="ai-panel-{{ $loopIndex }}" class="hidden">
                            <td colspan="12" class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center gap-2 mb-3">
                                    <x-heroicon-o-sparkles class="w-4 h-4 text-cyan-500" />
                                    <span class="text-sm font-semibold text-gray-800">AI Scheduling Suggestions</span>
                                    <span class="text-xs text-gray-400">— {{ $equipment?->equipment_name }}</span>
                                    <button type="button"
                                        class="ml-auto text-xs text-gray-400 hover:text-gray-600 close-ai-panel"
                                        data-conflict-index="{{ $loopIndex }}">
                                        ✕ Close
                                    </button>
                                </div>
                                <div id="ai-content-{{ $loopIndex }}" class="text-sm text-gray-600">
                                    <p class="text-gray-400 italic">Loading AI suggestions…</p>
                                </div>
                            </td>
                        </tr>

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
                                <button type="button"
                                    class="sc-call-needed-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-red-700 text-white hover:bg-red-800 transition shadow-sm"
                                    data-equipment-name="{{ $dEquipment?->equipment_name }}"
                                    data-equipment-id="{{ $dEquipment?->equipment_id }}"
                                    data-category="{{ $dEquipment?->productCategory?->title }}">
                                    <x-heroicon-o-phone class="w-3.5 h-3.5" />
                                    Call Needed
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

                {{-- AI panel for this damaged equipment group --}}
                <tr id="ai-panel-d{{ $dLoopIndex }}" class="hidden">
                    <td colspan="12" class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-o-sparkles class="w-4 h-4 text-cyan-500" />
                            <span class="text-sm font-semibold text-gray-800">AI Scheduling Suggestions</span>
                            <span class="text-xs text-gray-400">— {{ $dEquipment?->equipment_name }}</span>
                            <button type="button"
                                class="ml-auto text-xs text-gray-400 hover:text-gray-600 close-ai-panel"
                                data-conflict-index="d{{ $dLoopIndex }}">
                                ✕ Close
                            </button>
                        </div>
                        <div id="ai-content-d{{ $dLoopIndex }}" class="text-sm text-gray-600">
                            <p class="text-gray-400 italic">Loading AI suggestions…</p>
                        </div>
                    </td>
                </tr>

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

{{-- Damaged Equipment Call Needed Modal --}}
<div id="DamagedCallNeededModal"
    style="display: none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="w-full mx-auto max-w-lg">
        <div class="bg-white rounded-lg shadow-xl w-full border border-gray-200 overflow-hidden max-h-[90vh] flex flex-col">

            <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add Call Reminder</h2>
                    <p class="text-sm text-gray-500">Assign customer call reminder</p>
                </div>
                <button type="button" onclick="closeDamagedCallModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="overflow-y-auto px-6 pt-6 pb-5 space-y-4">

                {{-- Call For --}}
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Call For</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="sc_contact_type" value="customer" checked class="focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Customer</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="sc_contact_type" value="manual" class="focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Other-Customer</span>
                        </label>
                    </div>
                </div>

                {{-- Customer select --}}
                <div id="sc-customer-section" class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Customer</label>
                    <select id="sc_call_customer_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-900">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $customer)
                            @php
                                $cnFull  = trim((string) $customer->full_name);
                                $cnPhone = trim((string) $customer->phone);
                            @endphp
                            @if ($cnFull || $cnPhone)
                                <option value="{{ $customer->id }}">
                                    {{ $cnFull }}{{ $cnPhone ? '      ' . \App\Helpers\CustomHelper::formatPhone($cnPhone) : '' }}{{ $customer->email ? '      ' . $customer->email : '' }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Manual contact --}}
                <div id="sc-manual-contact-section" class="hidden space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Name</label>
                            <input type="text" id="sc_contact_name" placeholder="Enter Name"
                                class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Phone</label>
                            <input type="text" id="sc_contact_phone" placeholder="(xxx) xxx-xxxx"
                                class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="sc_contact_email" placeholder="Enter Email"
                            class="w-full rounded-md border border-gray-300 px-3 py-3 text-sm">
                    </div>
                </div>

                {{-- Assign To --}}
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Assign To</label>
                    <select id="sc_call_assigned_to"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-900">
                        <option value="">Select Assignee</option>
                        @foreach ($callUsers as $cu)
                            <option value="{{ $cu->id }}">{{ $cu->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Reason --}}
                <div class="w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Reason</label>
                    <select id="sc_call_reason"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-900">
                        <option value="">Select Reason</option>
                        <option value="contract_renewal">Contract Renewal</option>
                        <option value="delivery_pickup">Delivery / Pickup</option>
                        <option value="equipment_availability">Equipment Availability</option>
                        <option value="equipment_return">Equipment Return</option>
                        <option value="general_followup">General Follow-up</option>
                        <option value="maintenance_request">Maintenance Request</option>
                        <option value="order_review">Order Review</option>
                        <option value="payment_followup">Payment Follow-up</option>
                        <option value="rental_inquiry">Rental Inquiry</option>
                    </select>
                </div>

                {{-- Urgent (pre-checked) --}}
                <div class="flex items-center rounded-lg border border-gray-200 p-3 bg-gray-50">
                    <input type="checkbox" id="sc_call_is_urgent" checked
                        class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <label for="sc_call_is_urgent" class="ml-3 text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-red-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008zM10.29 3.86 1.82 18a2.25 2.25 0 0 0 1.93 3.375h16.5A2.25 2.25 0 0 0 22.18 18L13.71 3.86a2.25 2.25 0 0 0-3.42 0Z" />
                            </svg>
                            <span>Mark as Urgent</span>
                        </span>
                        <span class="block text-xs text-gray-500 font-normal mt-1">High priority call reminder</span>
                    </label>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="sc_call_notes" rows="3" placeholder="Enter call notes..."
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700"></textarea>
                </div>

                {{-- Damaged equipment badge (populated by JS) --}}
                <div id="sc-damaged-badge-container" class="hidden">
                    <a id="sc-damaged-badge-link" href="#"
                        class="inline-flex items-center flex-wrap gap-2 px-3 py-2 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 transition-colors text-sm cursor-pointer">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-500 text-white tracking-wide">DAMAGED</span>
                        <span id="sc-damaged-badge-name" class="font-semibold text-gray-900"></span>
                        <span id="sc-damaged-badge-id" class="text-xs font-mono text-gray-600 bg-white border border-gray-200 rounded px-1.5 py-0.5"></span>
                        <span id="sc-damaged-badge-category" class="text-xs text-gray-500"></span>
                    </a>
                    <p class="text-xs text-gray-400 mt-1">Click badge to view this conflict in Schedule Conflicts.</p>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" onclick="closeDamagedCallModal()"
                        class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" id="sc-call-save-btn" onclick="saveDamagedCallNeeded()"
                        class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2 hover:bg-teal-700 transition">
                        <span id="scCallBtnText">Save</span>
                        <svg id="scCallBtnSpinner" xmlns="http://www.w3.org/2000/svg"
                            class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>

            </div>
        </div>
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

    // ── AI Suggest ───────────────────────────────────────────────────────────
    const aiAdvisorUrlTemplate = '{{ route('admin.order-management.schedules.ai.show', ['orderProductId' => '__OP_ID__']) }}';

    function escapeHtml(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    document.addEventListener('click', function (e) {
        // Toggle AI panel open
        const aiBtn = e.target.closest('.ai-suggest-btn');
        if (aiBtn) {
            const idx     = aiBtn.dataset.conflictIndex;
            const opId    = aiBtn.dataset.orderProductId;
            const panel   = document.getElementById('ai-panel-' + idx);
            const content = document.getElementById('ai-content-' + idx);
            if (!panel || !content) return;

            if (!panel.classList.contains('hidden')) {
                panel.classList.add('hidden');
                return;
            }

            panel.classList.remove('hidden');
            content.innerHTML = '<p class="text-gray-400 italic text-sm">Loading AI suggestions…</p>';

            const url = aiAdvisorUrlTemplate.replace('__OP_ID__', opId);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(r => r.json().catch(() => null).then(d => ({ ok: r.ok, data: d })))
                .then(({ ok, data }) => {
                    if (!ok || !data?.success) {
                        content.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">${escapeHtml(data?.message || 'AI advisor returned an error.')}</div>`;
                        return;
                    }
                    const assistant = data.data?.assistant || {};
                    const ai        = data.data?.ai || {};
                    const rec       = ai.recommendation || {};

                    const issuesHtml = (assistant.issues || []).map(i =>
                        `<div class="mb-2 rounded-lg border border-yellow-200 bg-yellow-50 p-3"><div class="font-medium text-yellow-800">${escapeHtml(i.code)}</div><div class="text-sm text-yellow-700">${escapeHtml(i.message)}</div></div>`
                    ).join('');

                    const reasoningHtml = (rec.reasoning || []).map(r => `<li>${escapeHtml(r)}</li>`).join('');
                    const actionsHtml   = (rec.actions_required || []).map(a =>
                        `<span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700">${escapeHtml(a)}</span>`
                    ).join('');
                    const warningsHtml  = (rec.warnings || []).map(w => `<li>${escapeHtml(w)}</li>`).join('');
                    const altsHtml = (rec.alternatives || []).map(opt => `
                        <div class="rounded-xl border p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <span class="font-semibold text-sm">${escapeHtml(opt.equipment_name || 'No equipment')}</span>
                                    <span class="ml-2 text-xs text-gray-500">#${escapeHtml(opt.equipment_id ?? '-')}</span>
                                </div>
                                <span class="text-xs uppercase tracking-wide text-gray-500">${escapeHtml(opt.relationship_type)}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">${escapeHtml(opt.summary)}</p>
                            ${(opt.actions_required||[]).length ? `<div class="mt-2 flex flex-wrap gap-2">${opt.actions_required.map(a=>`<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">${escapeHtml(a)}</span>`).join('')}</div>` : ''}
                        </div>`
                    ).join('');

                    content.innerHTML = `<div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-gray-100 p-3"><div class="text-xs uppercase tracking-wide text-gray-500">Delivery</div><div class="font-medium text-sm">${escapeHtml(assistant.order_window?.delivery||'-')}</div></div>
                            <div class="rounded-lg bg-gray-100 p-3"><div class="text-xs uppercase tracking-wide text-gray-500">Pickup</div><div class="font-medium text-sm">${escapeHtml(assistant.order_window?.pickup||'-')}</div></div>
                        </div>
                        ${issuesHtml ? `<div><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Operational Issues</h3>${issuesHtml}</div>` : ''}
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-sky-600">AI Decision</div>
                                    <div class="mt-1 font-semibold text-sky-900">${escapeHtml(rec.decision||'No decision')}</div>
                                    <div class="text-sm text-sky-800 mt-0.5">${escapeHtml(rec.recommended_equipment_name||'No equipment recommended')}</div>
                                </div>
                                <div class="text-right text-xs text-sky-700 shrink-0">
                                    <div>ID: ${escapeHtml(rec.recommended_equipment_id??'-')}</div>
                                    <div>${escapeHtml(rec.relationship_type||'')}</div>
                                </div>
                            </div>
                            ${actionsHtml ? `<div class="mt-3 flex flex-wrap gap-2">${actionsHtml}</div>` : ''}
                        </div>
                        ${reasoningHtml ? `<div><h3 class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Reasoning</h3><ul class="list-disc space-y-1 pl-5 text-sm text-gray-700">${reasoningHtml}</ul></div>` : ''}
                        ${warningsHtml ? `<div><h3 class="mb-1 text-xs font-semibold uppercase tracking-wide text-amber-700">Warnings</h3><ul class="list-disc space-y-1 pl-5 text-sm text-amber-700">${warningsHtml}</ul></div>` : ''}
                        ${altsHtml ? `<div><h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Alternatives</h3><div class="space-y-2">${altsHtml}</div></div>` : ''}
                        ${ai.error ? `<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">${escapeHtml(ai.error)}</div>` : ''}
                    </div>`;
                })
                .catch(err => {
                    console.error('AI Suggest fetch error:', err);
                    content.innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">Request failed. Please try again.</div>';
                });
        }

        // Close AI panel
        const closeAiBtn = e.target.closest('.close-ai-panel');
        if (closeAiBtn) {
            const idx = closeAiBtn.dataset.conflictIndex;
            document.getElementById('ai-panel-' + idx)?.classList.add('hidden');
        }
    });

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

    // ── Damaged Equipment Call Needed Modal ───────────────────────────────────
    const scConflictsUrl = '{{ route('admin.order-management.schedule-conflicts.index', ['section' => 'damaged']) }}';

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.sc-call-needed-btn');
        if (!btn) return;

        const equipName     = btn.dataset.equipmentName || '';
        const equipId       = btn.dataset.equipmentId   || '';
        const equipCategory = btn.dataset.category       || '';

        // Reset fields
        document.getElementById('sc_call_customer_id').value = '';
        document.getElementById('sc_call_assigned_to').value = '';
        document.getElementById('sc_call_reason').value      = 'equipment_availability';
        document.getElementById('sc_call_notes').value       = '';
        document.getElementById('sc_call_is_urgent').checked = true;
        const scContactName  = document.getElementById('sc_contact_name');
        const scContactEmail = document.getElementById('sc_contact_email');
        const scContactPhone = document.getElementById('sc_contact_phone');
        if (scContactName)  scContactName.value  = '';
        if (scContactEmail) scContactEmail.value = '';
        if (scContactPhone) scContactPhone.value = '';

        // Reset to Customer radio
        const scCustomerRadio = document.querySelector('input[name="sc_contact_type"][value="customer"]');
        if (scCustomerRadio) scCustomerRadio.checked = true;
        document.getElementById('sc-customer-section').classList.remove('hidden');
        document.getElementById('sc-manual-contact-section').classList.add('hidden');

        // Populate and show the damaged badge
        if (equipName) {
            document.getElementById('sc-damaged-badge-name').textContent     = equipName;
            document.getElementById('sc-damaged-badge-id').textContent       = equipId ? 'ID: ' + equipId : '';
            document.getElementById('sc-damaged-badge-category').textContent = equipCategory;
            document.getElementById('sc-damaged-badge-link').href            = scConflictsUrl;
            document.getElementById('sc-damaged-badge-container').classList.remove('hidden');

            document.getElementById('sc_call_notes').value =
                'Re: Damaged equipment – ' + equipName +
                (equipId ? ' (ID: ' + equipId + ')' : '') +
                '. Calling to expedite repair and discuss alternative arrangements.';
        } else {
            document.getElementById('sc-damaged-badge-container').classList.add('hidden');
        }

        const modal = document.getElementById('DamagedCallNeededModal');
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    });

    // Contact type toggle for SC modal
    document.addEventListener('change', function (e) {
        if (e.target.name !== 'sc_contact_type') return;
        const isCustomer = e.target.value === 'customer';
        document.getElementById('sc-customer-section').classList.toggle('hidden', !isCustomer);
        document.getElementById('sc-manual-contact-section').classList.toggle('hidden', isCustomer);
    });
});
</script>

<script>
function closeDamagedCallModal() {
    const modal = document.getElementById('DamagedCallNeededModal');
    modal.style.display = 'none';
    modal.classList.add('hidden');
}

function saveDamagedCallNeeded() {
    const contactType  = document.querySelector('input[name="sc_contact_type"]:checked')?.value;
    const customerId   = document.getElementById('sc_call_customer_id').value;
    const contactName  = document.getElementById('sc_contact_name')?.value.trim()  || '';
    const contactEmail = document.getElementById('sc_contact_email')?.value.trim() || '';
    const contactPhone = document.getElementById('sc_contact_phone')?.value.trim() || '';
    const reason       = document.getElementById('sc_call_reason').value;
    const notes        = document.getElementById('sc_call_notes').value;
    const assignedTo   = document.getElementById('sc_call_assigned_to').value;
    const isUrgent     = document.getElementById('sc_call_is_urgent').checked;

    if (contactType === 'customer' && !customerId) {
        if (window.notyf) notyf.error('Please select a customer.');
        return;
    }
    if (contactType === 'manual' && !contactName) {
        if (window.notyf) notyf.error('Please enter a name.');
        return;
    }
    if (contactType === 'manual' && !contactPhone) {
        if (window.notyf) notyf.error('Please enter a phone number.');
        return;
    }
    if (!reason) {
        if (window.notyf) notyf.error('Please select a reason.');
        return;
    }
    if (!assignedTo) {
        if (window.notyf) notyf.error('Please select an assignee.');
        return;
    }

    const btn     = document.getElementById('sc-call-save-btn');
    const btnText = document.getElementById('scCallBtnText');
    const spinner = document.getElementById('scCallBtnSpinner');
    btn.disabled = true;
    btnText.textContent = 'Saving...';
    spinner.classList.remove('hidden');

    fetch('{{ route('admin.dashboard.call-needed.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            customer_id:   contactType === 'customer' ? customerId : null,
            contact_name:  contactName  || null,
            contact_email: contactEmail || null,
            contact_phone: contactPhone || null,
            assigned_to:   assignedTo,
            reason:        reason,
            notes:         notes,
            is_urgent:     isUrgent ? 1 : 0,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (window.notyf) notyf.success(data.message || 'Call reminder created.');
            closeDamagedCallModal();
        } else {
            if (window.notyf) notyf.error(data.message || 'Something went wrong.');
        }
    })
    .catch(err => {
        console.error('Save call needed error:', err);
        if (window.notyf) notyf.error('Request failed. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Save';
        spinner.classList.add('hidden');
    });
}
</script>
@endpush

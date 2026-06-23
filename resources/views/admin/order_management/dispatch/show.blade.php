@extends('admin.layouts.app')

@section('title', 'Dispatch Detail')

@push('css')
<style>
    .sop-row    { @apply flex items-center gap-3 py-3 border-b border-gray-100 last:border-0; }
    .option-row { @apply flex items-center gap-3 py-3 border-b border-gray-100 last:border-0; }
    .dispatch-checkbox {
        @apply w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 cursor-pointer flex-shrink-0;
    }
    .result-select {
        @apply border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 bg-gray-50
               focus:outline-none focus:ring-1 focus:ring-blue-400 ml-auto;
    }
</style>
@endpush

@section('content')

@include('flash::message')

@php
    $order         = $orderProduct->order;
    $addr          = $order?->shippingAddress;
    $orderUid      = $order?->unique_id;
    $scheduleRoute = route('admin.order-management.orders.update-product-schedule', [':order_uid', ':product_uid']);
@endphp

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.order-management.dispatch.index') }}"
           class="text-gray-500 hover:text-gray-700">
            <x-heroicon-o-arrow-left class="w-5 h-5" />
        </a>
        <div>
            <h1 class="text-xl font-semibold flex items-center gap-2">
                <x-heroicon-o-truck class="w-6 h-6 text-blue-600" />
                Dispatch Detail
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Order <span class="font-semibold text-gray-700">#{{ $order?->order_number }}</span>
                &mdash; {{ $orderProducts->count() }} {{ Str::plural('product', $orderProducts->count()) }}
            </p>
        </div>
    </div>
    <a href="{{ route('admin.order-management.orders.edit', $orderUid) }}"
       class="text-sm text-blue-600 hover:underline flex items-center gap-1">
        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
        View Full Order
    </a>
</div>

{{-- ════════════════════════════════════════════════════════════════════════════
     TOP GRID  (3 cols)
     Col 1 Row 1 = Delivery Information
     Col 1 Row 2 = Driver Assigned
     Col 2-3 Row 1 = Delivery + Return schedule block (one per order product)
════════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    {{-- ── COLUMN 1 ──────────────────────────────────────────────────────── --}}
    <div class="space-y-5">

        {{-- Row 1: Delivery Information --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Delivery Information</h2>
            </div>
            <div class="px-5 py-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Order ID:</span>
                    <span class="font-semibold text-gray-800">#{{ $order?->order_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Customer Name:</span>
                    <span class="font-medium text-gray-800 text-right">{{ $order?->customer_name ?? '—' }}</span>
                </div>
                @if ($addr?->email)
                <div class="flex justify-between items-center gap-2">
                    <span class="text-gray-500 flex-shrink-0">Email:</span>
                    <a href="mailto:{{ $addr->email }}"
                       class="text-blue-600 hover:underline text-xs truncate">{{ $addr->email }}</a>
                </div>
                @endif
                @if ($addr?->phone)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Phone:</span>
                    <a href="tel:{{ preg_replace('/\D/', '', $addr->phone) }}"
                       class="text-gray-800 font-medium">{{ $addr->phone }}</a>
                </div>
                @endif
                @if ($addr)
                <div class="flex flex-col gap-0.5 pt-1.5 border-t border-gray-100">
                    <span class="text-gray-500 text-xs">Billing Address:</span>
                    <span class="text-gray-700 text-xs leading-snug">{{ $addr->full_address }}</span>
                    <a href="https://maps.google.com/?q={{ urlencode($addr->full_address) }}"
                       target="_blank"
                       class="text-blue-500 hover:underline text-xs mt-0.5">See on maps</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Row 2: Driver Assigned --}}
        <div class="bg-white rounded-xl border-2 border-orange-300 shadow-sm px-5 py-4
                    flex items-center justify-between">
            <p class="text-sm font-semibold text-orange-600">
                Driver Assigned:
                <span class="text-orange-700">
                    {{ $orderProduct->deliveryEmployee?->full_name ?? 'Not Assigned' }}
                </span>
            </p>
            <a href="{{ route('admin.order-management.orders.edit', $orderUid) }}"
               class="text-gray-400 hover:text-gray-600" title="Edit in Order">
                <x-heroicon-o-pencil-square class="w-4 h-4" />
            </a>
        </div>

    </div>{{-- end col 1 --}}


    {{-- ── COLUMN 2-3 : Schedule block(s) ───────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-5">

        @foreach ($orderProducts as $op)
        @php
            $delivStatus = $op->delivery_status ?? 'Pending';
            $pickStatus  = $op->pickup_status  ?? 'Pending';
            $allowPickup = in_array($op->delivery_status, ['Completed', 'Close as Completed'], true);
        @endphp

        {{-- Product name header --}}
        @if ($orderProducts->count() > 1)
        <h3 class="text-base font-semibold text-gray-700 mt-2">{{ $op->product_name }}</h3>
        @else
        <h2 class="text-lg font-semibold text-gray-800">{{ $op->product_name }}</h2>
        @endif

        {{-- ── Delivery Schedule (same format as Orders edit page) ────────── --}}
        <div class="space-y-2" x-data="{ deliveryStatus: '{{ $delivStatus }}' }">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                <x-heroicon-o-arrow-right-circle class="w-5 h-5" /> Delivery Schedule
            </div>
            <div class="bg-white border rounded-xl p-3">
                <div class="flex flex-wrap items-center gap-2 w-full">
                    {{-- Date --}}
                    <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Date</label>
                        <input type="text"
                            id="delivery_date_{{ $op->unique_id }}"
                            data-format="{{ config('app.date.js_date_format') }}"
                            value="{{ $op->delivery_date ? \App\Helpers\CustomHelper::formatDate($op->delivery_date) : '' }}"
                            placeholder="Select date"
                            class="datepicker delivery_date border rounded px-3 py-3 text-xs w-full" />
                    </div>
                    {{-- Time --}}
                    <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Time</label>
                        <input type="text"
                            id="delivery_time_{{ $op->unique_id }}"
                            value="{{ $op->delivery_time ? \App\Helpers\CustomHelper::formatTime($op->delivery_time) : '' }}"
                            placeholder="Select time"
                            class="delivery_time border rounded px-3 py-3 text-xs w-full" />
                    </div>
                    {{-- Type --}}
                    <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Type</label>
                        <div x-data="{ selected: '{{ $op->delivery_transport_mode ?? 'Store' }}', open: false }"
                             class="relative w-full">
                            <button type="button" @click="open = !open"
                                class="border rounded px-px-3 py-3 text-xs w-full flex items-center justify-center
                                       focus:outline-none delivery_transport_mode">
                                <template x-if="selected === 'Store'">
                                    <x-heroicon-o-building-storefront class="w-4 h-4"
                                        x-bind:class="(deliveryStatus==='Completed'||deliveryStatus==='Close as Completed')
                                            ? 'text-green-600' : 'text-yellow-600'" />
                                </template>
                                <template x-if="selected === 'Truck'">
                                    <x-heroicon-o-truck class="w-4 h-4"
                                        x-bind:class="(deliveryStatus==='Completed'||deliveryStatus==='Close as Completed')
                                            ? 'text-green-600' : 'text-yellow-600'" />
                                </template>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                 class="absolute z-10 mt-1 w-max bg-white border rounded shadow-lg">
                                <ul>
                                    <li>
                                        <button type="button"
                                            @click="selected='Store'; open=false;
                                                    $nextTick(()=>$refs.dtypeInput{{ $loop->index }}.dispatchEvent(new Event('change')));"
                                            class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                            <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button"
                                            @click="selected='Truck'; open=false;
                                                    $nextTick(()=>$refs.dtypeInput{{ $loop->index }}.dispatchEvent(new Event('change')));"
                                            class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                            <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <input type="hidden" class="delivery_transport_mode"
                                :value="selected"
                                x-ref="dtypeInput{{ $loop->index }}">
                        </div>
                    </div>
                    {{-- Status --}}
                    <div class="flex flex-col items-start min-w-[70px] flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Status</label>
                        <select class="delivery_status border rounded px-3 py-3 text-xs w-full"
                                x-model="deliveryStatus">
                            <option value="Pending"            {{ $delivStatus==='Pending'            ? 'selected':'' }}>Pending</option>
                            <option value="Completed"          {{ $delivStatus==='Completed'          ? 'selected':'' }}
                                                               {{ $delivStatus==='Close as Completed' ? 'disabled':'' }}>Completed</option>
                            <option value="Close as Completed" {{ $delivStatus==='Close as Completed' ? 'selected':'' }}
                                                               {{ $delivStatus==='Completed'          ? 'disabled':'' }}>Close as Completed</option>
                            <option value="Reschedule"         {{ $delivStatus==='Reschedule'         ? 'selected':'' }}>Reschedule</option>
                        </select>
                    </div>
                    {{-- Pickup Store --}}
                    <div class="flex flex-col items-start min-w-[70px] flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Pickup Store</label>
                        <select class="delivery_store_id border rounded px-3 py-3 text-xs w-full">
                            <option value="" disabled>Select Pickup Store</option>
                            @foreach ($stores as $s)
                                <option value="{{ $s->id }}" {{ $op->delivery_store_id==$s->id ? 'selected':'' }}>
                                    {{ $s->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Technician --}}
                    <div class="flex flex-col items-start min-w-[90px] flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Technician</label>
                        <select class="delivery_by border rounded px-3 py-3 text-xs w-full">
                            <option value="" disabled>Select Technician</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $op->delivery_by==$emp->id ? 'selected':'' }}>
                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Return Schedule (same format as Orders edit page) ──────────── --}}
        <div class="space-y-2" x-data="{ pickupStatus: '{{ $pickStatus }}' }">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                <x-heroicon-o-arrow-left-circle class="w-5 h-5" /> Return Schedule
            </div>
            <div class="bg-white border rounded-xl p-3">
                <div class="flex flex-wrap items-center gap-2 w-full">
                    {{-- Date --}}
                    <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Date</label>
                        <input type="text"
                            id="pickup_date_{{ $op->unique_id }}"
                            data-format="{{ config('app.date.js_date_format') }}"
                            value="{{ $op->pickup_date ? \App\Helpers\CustomHelper::formatDate($op->pickup_date) : '' }}"
                            placeholder="Select date"
                            class="datepicker pickup_date border rounded px-3 py-3 text-xs w-full" />
                    </div>
                    {{-- Time --}}
                    <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Time</label>
                        <input type="text"
                            id="pickup_time_{{ $op->unique_id }}"
                            value="{{ $op->pickup_time ? \App\Helpers\CustomHelper::formatTime($op->pickup_time) : '' }}"
                            placeholder="Select time"
                            class="pickup_time border rounded px-3 py-3 text-xs w-full" />
                    </div>
                    {{-- Type --}}
                    <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Type</label>
                        <div x-data="{ selected: '{{ $op->pickup_transport_mode ?? 'Store' }}', open: false }"
                             class="relative w-full">
                            <button type="button" @click="open = !open"
                                class="border rounded px-px-3 py-3 text-xs w-full flex items-center justify-center
                                       focus:outline-none pickup_transport_mode">
                                <template x-if="selected === 'Store'">
                                    <x-heroicon-o-building-storefront class="w-4 h-4"
                                        x-bind:class="pickupStatus==='Completed' ? 'text-green-600' : 'text-yellow-600'" />
                                </template>
                                <template x-if="selected === 'Truck'">
                                    <x-heroicon-o-truck class="w-4 h-4"
                                        x-bind:class="pickupStatus==='Completed' ? 'text-green-600' : 'text-yellow-600'" />
                                </template>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                 class="absolute z-10 mt-1 w-max bg-white border rounded shadow-lg">
                                <ul>
                                    <li>
                                        <button type="button"
                                            @click="selected='Store'; open=false;
                                                    $nextTick(()=>$refs.rtypeInput{{ $loop->index }}.dispatchEvent(new Event('change')));"
                                            class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                            <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button"
                                            @click="selected='Truck'; open=false;
                                                    $nextTick(()=>$refs.rtypeInput{{ $loop->index }}.dispatchEvent(new Event('change')));"
                                            class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                            <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <input type="hidden" class="pickup_transport_mode"
                                :value="selected"
                                x-ref="rtypeInput{{ $loop->index }}">
                        </div>
                    </div>
                    {{-- Status --}}
                    <div class="flex flex-col items-start min-w-[70px] flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Status</label>
                        <select class="pickup_status border rounded px-3 py-3 text-xs w-full"
                                x-model="pickupStatus"
                                {{ !$allowPickup ? 'disabled' : '' }}>
                            <option value="Pending"   {{ $pickStatus==='Pending'   ? 'selected':'' }}>Pending</option>
                            <option value="Completed" {{ $pickStatus==='Completed' ? 'selected':'' }}>Completed</option>
                        </select>
                    </div>
                    {{-- Return Store --}}
                    <div class="flex flex-col items-start min-w-[70px] flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Return Store</label>
                        <select class="pickup_store_id border rounded px-3 py-3 text-xs w-full">
                            <option value="" disabled>Select Return Store</option>
                            @foreach ($stores as $s)
                                <option value="{{ $s->id }}" {{ $op->pickup_store_id==$s->id ? 'selected':'' }}>
                                    {{ $s->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Technician --}}
                    <div class="flex flex-col items-start min-w-[90px] flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Technician</label>
                        <select class="pickup_by border rounded px-3 py-3 text-xs w-full">
                            <option value="" disabled>Select Technician</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $op->pickup_by==$emp->id ? 'selected':'' }}>
                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        @endforeach{{-- end schedule loop --}}

    </div>{{-- end col 2-3 --}}

</div>{{-- end top grid --}}


{{-- ════════════════════════════════════════════════════════════════════════════
     CHECKLISTS — one card per order product, stacked
════════════════════════════════════════════════════════════════════════════ --}}
<div class="mt-6 space-y-6">

    @foreach ($orderProducts as $op)
    @php
        $cl          = $op->dispatch_checklist ?? [];
        $sop         = $cl['sop']              ?? [];
        $optChecks   = $cl['product_options']  ?? [];
        $relChecks   = $cl['related_products'] ?? [];
        $productOpts = $op->product_data['product_option_items'] ?? [];
        $relatedProds = $orderProducts->where('unique_id', '!=', $op->unique_id);
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
         data-dispatch-product="{{ $op->unique_id }}">

        {{-- Card header --}}
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">{{ $op->product_name }}</h3>
            @if ($cl['updated_at'] ?? null)
                <span class="text-xs text-gray-400">
                    Last saved: {{ \Carbon\Carbon::parse($cl['updated_at'])->format('M d, Y g:i A') }}
                </span>
            @endif
        </div>

        <div class="px-5 py-5 space-y-5">

            {{-- ── A. SOP ─────────────────────────────────────────────────── --}}
            <div>
                <h4 class="text-sm font-semibold text-red-600 mb-2">Standard Operating Procedures (SOP)</h4>
                <div class="border border-gray-200 rounded-xl px-4 divide-y divide-gray-100">

                    <div class="sop-row">
                        <input type="checkbox" class="dispatch-checkbox"
                            data-section="sop" data-key="customer_called"
                            {{ ($sop['customer_called'] ?? false) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-red-600 {{ ($sop['customer_called'] ?? false) ? 'line-through text-gray-400' : '' }}">
                            Customer Called
                        </span>
                    </div>

                    <div class="sop-row">
                        <input type="checkbox" class="dispatch-checkbox"
                            data-section="sop" data-key="customer_texted"
                            {{ ($sop['customer_texted'] ?? false) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-red-600 {{ ($sop['customer_texted'] ?? false) ? 'line-through text-gray-400' : '' }}">
                            Customer Texted
                        </span>
                    </div>

                    <div class="sop-row">
                        <input type="checkbox" class="dispatch-checkbox"
                            data-section="sop" data-key="keys_checked"
                            {{ !empty($sop['keys']) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-red-600">Keys</span>
                        <select class="result-select sop-result-select" data-key="keys">
                            <option value="">— Result —</option>
                            @foreach (['Full Set', '1 Key', 'N/A'] as $opt)
                                <option value="{{ $opt }}" {{ ($sop['keys'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sop-row">
                        <input type="checkbox" class="dispatch-checkbox"
                            data-section="sop" data-key="fuel_checked"
                            {{ !empty($sop['fuel']) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-red-600">Fuel</span>
                        <select class="result-select sop-result-select" data-key="fuel">
                            <option value="">— Level —</option>
                            @foreach (['Full', '3/4', '1/2', '1/4', 'Empty'] as $opt)
                                <option value="{{ $opt }}" {{ ($sop['fuel'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            {{-- ── B. Product Options ──────────────────────────────────────── --}}
            @if (!empty($productOpts))
            <div>
                <h4 class="text-sm font-semibold text-red-600 mb-2">Product Options</h4>
                <div class="border border-gray-200 rounded-xl px-4 divide-y divide-gray-100">
                    @foreach ($productOpts as $idx => $opt)
                    @php $optKey = 'opt_' . $idx; @endphp
                    <div class="option-row">
                        <input type="checkbox" class="dispatch-checkbox"
                            data-section="product_options" data-key="{{ $optKey }}"
                            {{ ($optChecks[$optKey] ?? false) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-red-600 {{ ($optChecks[$optKey] ?? false) ? 'line-through text-gray-400' : '' }}">
                            {{ $opt['name'] ?? 'Option' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ── C. Related Products ─────────────────────────────────────── --}}
            @if ($relatedProds->isNotEmpty())
            <div>
                <h4 class="text-sm font-semibold text-red-600 mb-2">Related Products</h4>
                <div class="border border-gray-200 rounded-xl px-4 divide-y divide-gray-100">
                    @foreach ($relatedProds as $rel)
                    <div class="option-row">
                        <input type="checkbox" class="dispatch-checkbox"
                            data-section="related_products" data-key="{{ $rel->unique_id }}"
                            {{ ($relChecks[$rel->unique_id] ?? false) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-red-600 {{ ($relChecks[$rel->unique_id] ?? false) ? 'line-through text-gray-400' : '' }}">
                            {{ $rel->product_name }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Save + status --}}
            <div class="flex items-center justify-between pt-1">
                <span class="save-status text-xs text-green-600 font-semibold hidden">✓ Checklist saved</span>
                <div class="ml-auto">
                    <button type="button"
                        class="save-checklist-btn bg-blue-600 hover:bg-blue-700 text-white
                               text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                        Save Checklist
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endforeach

</div>{{-- end checklists --}}


{{-- ════════════════════════════════════════════════════════════════════════════
     BOTTOM ROW  — Instructions + Completed By
════════════════════════════════════════════════════════════════════════════ --}}
<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Order / Delivery Instructions --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Order / Delivery Instructions</h2>
            <a href="{{ route('admin.order-management.orders.edit', $orderUid) }}"
               target="_blank"
               class="flex items-center gap-1.5 text-xs font-medium bg-blue-600 hover:bg-blue-700
                      text-white px-3 py-1.5 rounded-lg transition-colors">
                <x-heroicon-o-plus class="w-3.5 h-3.5" />
                Add Note
            </a>
        </div>
        <div id="notesList" class="px-5 py-4 min-h-[60px]">
            <p class="text-sm text-gray-400 italic">• No notes available.</p>
        </div>
    </div>

    {{-- Completed By --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-red-600">Completed By</h2>
        </div>
        <div class="px-5 py-4">
            @php $completedById = $orderProduct->dispatch_checklist['completed_by'] ?? null; @endphp
            <select id="completedBySelect"
                class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm w-full
                       focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">— Select Driver —</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $completedById == $emp->id ? 'selected' : '' }}>
                        {{ $emp->full_name }}
                    </option>
                @endforeach
            </select>
            <p id="completedByStatus" class="text-xs text-green-600 font-semibold mt-2 hidden">✓ Saved</p>
        </div>
    </div>

</div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const csrf         = document.querySelector('meta[name="csrf-token"]')?.content;
    const orderUid     = @json($orderUid);
    const schedUrlBase = @json($scheduleRoute);

    // ── Schedule: wire up per-product save-on-change ────────────────────────
    // Each product's schedule fields are scoped by their unique input IDs
    @foreach ($orderProducts as $op)
    (function () {
        const productUid = @json($op->unique_id);
        const schedUrl   = schedUrlBase
            .replace(':order_uid',   orderUid)
            .replace(':product_uid', productUid);

        function saveField(type, field, value) {
            const body = { _method: 'PUT', type: type };
            body[field] = value;
            apiFetch(schedUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            }).then(res => {
                window.notyf && notyf.success(res?.message || 'Schedule updated.');
            }).catch(() => window.notyf && notyf.error('Failed to update schedule.'));
        }

        // Delivery date
        const dDate = document.getElementById('delivery_date_' + productUid);
        if (dDate) dDate.addEventListener('change', function () { saveField('delivery', 'delivery_date', this.value); });

        // Delivery time
        const dTime = document.getElementById('delivery_time_' + productUid);
        if (dTime) flatpickr(dTime, { enableTime: true, noCalendar: true, dateFormat: 'h:i K', time_24hr: false,
            onClose: (_, dateStr) => { if (dateStr) saveField('delivery', 'delivery_time', dateStr); } });

        // Return date
        const rDate = document.getElementById('pickup_date_' + productUid);
        if (rDate) rDate.addEventListener('change', function () { saveField('return', 'pickup_date', this.value); });

        // Return time
        const rTime = document.getElementById('pickup_time_' + productUid);
        if (rTime) flatpickr(rTime, { enableTime: true, noCalendar: true, dateFormat: 'h:i K', time_24hr: false,
            onClose: (_, dateStr) => { if (dateStr) saveField('return', 'pickup_time', dateStr); } });

        // Delivery hidden inputs (transport mode) fire 'change' via Alpine
        document.querySelectorAll('#delivery_date_' + productUid)
            .forEach(() => {}); // placeholder — transport + dropdowns handled below

        // All selects and hidden transport inputs in schedule rows —
        // identify by scanning the sibling context of the date input
        const dDateEl = document.getElementById('delivery_date_' + productUid);
        if (dDateEl) {
            const schedBlock = dDateEl.closest('.space-y-2');
            if (schedBlock) {
                const dTransport = schedBlock.querySelector('input[type=hidden].delivery_transport_mode');
                const dStatus    = schedBlock.querySelector('.delivery_status');
                const dLocation  = schedBlock.querySelector('.delivery_store_id');
                const dTech      = schedBlock.querySelector('.delivery_by');
                if (dTransport) dTransport.addEventListener('change', function () { saveField('delivery', 'delivery_transport_mode', this.value); });
                if (dStatus)    dStatus.addEventListener('change',    function () { saveField('delivery', 'delivery_status',         this.value); });
                if (dLocation)  dLocation.addEventListener('change',  function () { saveField('delivery', 'delivery_store_id',       this.value); });
                if (dTech)      dTech.addEventListener('change',      function () { saveField('delivery', 'delivery_by',             this.value); });
            }
        }

        const rDateEl = document.getElementById('pickup_date_' + productUid);
        if (rDateEl) {
            const schedBlock = rDateEl.closest('.space-y-2');
            if (schedBlock) {
                const rTransport = schedBlock.querySelector('input[type=hidden].pickup_transport_mode');
                const rStatus    = schedBlock.querySelector('.pickup_status');
                const rLocation  = schedBlock.querySelector('.pickup_store_id');
                const rTech      = schedBlock.querySelector('.pickup_by');
                if (rTransport) rTransport.addEventListener('change', function () { saveField('return', 'pickup_transport_mode', this.value); });
                if (rStatus)    rStatus.addEventListener('change',    function () { saveField('return', 'pickup_status',         this.value); });
                if (rLocation)  rLocation.addEventListener('change',  function () { saveField('return', 'pickup_store_id',       this.value); });
                if (rTech)      rTech.addEventListener('change',      function () { saveField('return', 'pickup_by',             this.value); });
            }
        }
    })();
    @endforeach

    // ── Checklists: per-product save ────────────────────────────────────────
    document.querySelectorAll('[data-dispatch-product]').forEach(function (card) {
        const productUid = card.dataset.dispatchProduct;
        const saveUrl    = @json(route('admin.order-management.dispatch.checklist.save', ':uid'))
                             .replace(':uid', productUid);
        const saveStatus = card.querySelector('.save-status');
        let   saveTimer  = null;

        function buildState() {
            const state = { sop: {}, product_options: {}, related_products: {} };
            card.querySelectorAll('.dispatch-checkbox[data-section="sop"]').forEach(cb => {
                state.sop[cb.dataset.key] = cb.checked;
            });
            card.querySelectorAll('.sop-result-select').forEach(sel => {
                state.sop[sel.dataset.key] = sel.value || null;
            });
            card.querySelectorAll('.dispatch-checkbox[data-section="product_options"]').forEach(cb => {
                state.product_options[cb.dataset.key] = cb.checked;
            });
            card.querySelectorAll('.dispatch-checkbox[data-section="related_products"]').forEach(cb => {
                state.related_products[cb.dataset.key] = cb.checked;
            });
            return state;
        }

        function flashSaved() {
            clearTimeout(saveTimer);
            if (saveStatus) {
                saveStatus.classList.remove('hidden');
                saveTimer = setTimeout(() => saveStatus.classList.add('hidden'), 2500);
            }
        }

        function saveChecklist() {
            fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(buildState()),
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    flashSaved();
                    window.notyf && notyf.success('Checklist saved.');
                } else {
                    window.notyf && notyf.error('Failed to save checklist.');
                }
            }).catch(() => window.notyf && notyf.error('Failed to save checklist.'));
        }

        card.querySelector('.save-checklist-btn')?.addEventListener('click', saveChecklist);

        // Strikethrough on check
        card.querySelectorAll('.dispatch-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const row   = this.closest('.sop-row, .option-row');
                const label = row?.querySelector('span.text-sm');
                if (label) {
                    label.classList.toggle('line-through', this.checked);
                    label.classList.toggle('text-gray-400', this.checked);
                    label.classList.toggle('text-red-600',  !this.checked);
                }
            });
        });

        // Auto-check when result selected
        card.querySelectorAll('.sop-result-select').forEach(sel => {
            sel.addEventListener('change', function () {
                const cb = card.querySelector(`.dispatch-checkbox[data-key="${this.dataset.key}_checked"]`);
                if (cb && this.value) cb.checked = true;
            });
        });
    });

    // ── Completed By ────────────────────────────────────────────────────────
    const completedBySel    = document.getElementById('completedBySelect');
    const completedByStatus = document.getElementById('completedByStatus');
    const primarySaveUrl    = @json(route('admin.order-management.dispatch.checklist.save', ':uid'))
                                .replace(':uid', @json($orderProduct->unique_id));

    completedBySel?.addEventListener('change', function () {
        fetch(primarySaveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ completed_by: parseInt(this.value) || null }),
        }).then(r => r.json()).then(data => {
            if (data.success && completedByStatus) {
                completedByStatus.classList.remove('hidden');
                setTimeout(() => completedByStatus.classList.add('hidden'), 2000);
            }
        });
    });

    // ── Notes: load from order notes endpoint ────────────────────────────────
    const notesList = document.getElementById('notesList');
    if (notesList && orderUid) {
        const notesUrl = @json(route('admin.order-management.orders.notes.index', ':uid'))
                           .replace(':uid', orderUid);
        apiFetch(notesUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        }).then(res => {
            if (res?.success && res.html) notesList.innerHTML = res.html;
        }).catch(() => {});
    }
});
</script>
@endpush

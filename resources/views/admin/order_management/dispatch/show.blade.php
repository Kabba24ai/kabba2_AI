@extends('admin.layouts.app')

@section('title', 'Dispatch Detail')

@push('css')
<style>
    .sop-row      { @apply flex items-center gap-3 py-3 border-b border-gray-100 last:border-0; }
    .option-row   { @apply flex items-center gap-3 py-3 border-b border-gray-100 last:border-0; }
    .dispatch-checkbox {
        @apply w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 cursor-pointer flex-shrink-0;
    }
    .result-select {
        @apply border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-700 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-400 ml-auto;
    }
</style>
@endpush

@section('content')

@include('flash::message')

@php
    $order        = $orderProduct->order;
    $addr         = $order?->shippingAddress;
    $orderUid     = $order?->unique_id;
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ══════════════════════════════════════════════════════════════════════
         LEFT COLUMN  — Customer info + Driver
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="space-y-5">

        {{-- Delivery Information card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Delivery Information</h2>
                <span class="text-xs text-gray-400">Order ID: #{{ $order?->order_number }}</span>
            </div>
            <div class="px-5 py-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Customer Name:</span>
                    <span class="font-medium text-gray-800 text-right">{{ $order?->customer_name ?? '—' }}</span>
                </div>
                @if ($addr?->email)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Email:</span>
                    <a href="mailto:{{ $addr->email }}"
                       class="text-blue-600 hover:underline text-xs truncate max-w-[180px]">
                        {{ $addr->email }}
                    </a>
                </div>
                @endif
                @if ($addr?->phone)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Phone:</span>
                    <a href="tel:{{ preg_replace('/\D/', '', $addr->phone) }}"
                       class="text-gray-800 font-medium text-right">{{ $addr->phone }}</a>
                </div>
                @endif
                @if ($addr)
                <div class="flex flex-col gap-0.5 pt-1 border-t border-gray-100">
                    <span class="text-gray-500 text-xs">Billing Address:</span>
                    <span class="text-gray-700 text-xs leading-snug">{{ $addr->full_address }}</span>
                    <a href="https://maps.google.com/?q={{ urlencode($addr->full_address) }}"
                       target="_blank"
                       class="text-blue-500 hover:underline text-xs mt-0.5">See on maps</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Driver Assigned --}}
        <div class="bg-white rounded-xl border-2 border-orange-300 shadow-sm px-5 py-4 flex items-center justify-between">
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

    </div>{{-- end left column --}}


    {{-- ══════════════════════════════════════════════════════════════════════
         RIGHT COLUMN  — Per-product schedule + checklists
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-6">

        @foreach ($orderProducts as $op)
        @php
            $cl           = $op->dispatch_checklist ?? [];
            $sop          = $cl['sop']              ?? [];
            $optChecks    = $cl['product_options']  ?? [];
            $relChecks    = $cl['related_products'] ?? [];
            $productOpts  = $op->product_data['product_option_items'] ?? [];
            $relatedProds = $orderProducts->where('unique_id', '!=', $op->unique_id);
            $saveUrl      = route('admin.order-management.dispatch.checklist.save', $op->unique_id);
            $schedUrl     = str_replace([':order_uid', ':product_uid'], [$orderUid, $op->unique_id], $scheduleRoute);
            $delivStatus  = $op->delivery_status ?? 'Pending';
        @endphp

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
             data-dispatch-product="{{ $op->unique_id }}">

            {{-- Product header --}}
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-base font-semibold text-gray-800">{{ $op->product_name }}</h2>
                @if ($cl['updated_at'] ?? null)
                    <p class="text-xs text-gray-400 mt-0.5">
                        Checklist last saved: {{ \Carbon\Carbon::parse($cl['updated_at'])->format('M d, Y g:i A') }}
                    </p>
                @endif
            </div>

            <div class="px-5 py-5 space-y-6">

                {{-- ── Delivery Schedule ──────────────────────────────────────── --}}
                <div class="space-y-2" x-data="{ deliveryStatus: '{{ $delivStatus }}' }">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <x-heroicon-o-arrow-right-circle class="w-5 h-5" />
                        Delivery Schedule
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
                                        class="border rounded px-px-3 py-3 text-xs w-full flex items-center justify-center gap-1 focus:outline-none delivery_transport_mode">
                                        <template x-if="selected === 'Store'">
                                            <x-heroicon-o-building-storefront class="w-4 h-4"
                                                x-bind:class="(deliveryStatus==='Completed'||deliveryStatus==='Close as Completed') ? 'text-green-600' : 'text-yellow-600'" />
                                        </template>
                                        <template x-if="selected === 'Truck'">
                                            <x-heroicon-o-truck class="w-4 h-4"
                                                x-bind:class="(deliveryStatus==='Completed'||deliveryStatus==='Close as Completed') ? 'text-green-600' : 'text-yellow-600'" />
                                        </template>
                                    </button>
                                    <div x-show="open" @click.away="open = false"
                                         class="absolute z-10 mt-1 w-max bg-white border rounded shadow-lg">
                                        <ul>
                                            <li>
                                                <button type="button"
                                                    @click="selected='Store'; open=false; $nextTick(()=>$refs.typeInput_{{ $op->unique_id }}.dispatchEvent(new Event('change')));"
                                                    class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                                    <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button"
                                                    @click="selected='Truck'; open=false; $nextTick(()=>$refs.typeInput_{{ $op->unique_id }}.dispatchEvent(new Event('change')));"
                                                    class="w-full flex items-center justify-center px-3 py-3 hover:bg-yellow-100">
                                                    <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <input type="hidden" class="delivery_transport_mode"
                                        :value="selected"
                                        x-ref="typeInput_{{ $op->unique_id }}">
                                </div>
                            </div>
                            {{-- Status --}}
                            <div class="flex flex-col items-start min-w-[70px] flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-0.5">Status</label>
                                <select class="delivery_status border rounded px-3 py-3 text-xs w-full"
                                        x-model="deliveryStatus">
                                    <option value="Pending"             {{ $delivStatus === 'Pending'             ? 'selected' : '' }}>Pending</option>
                                    <option value="Completed"           {{ $delivStatus === 'Completed'           ? 'selected' : '' }}
                                                                        {{ $delivStatus === 'Close as Completed'  ? 'disabled' : '' }}>Completed</option>
                                    <option value="Close as Completed"  {{ $delivStatus === 'Close as Completed'  ? 'selected' : '' }}
                                                                        {{ $delivStatus === 'Completed'           ? 'disabled' : '' }}>Close as Completed</option>
                                    <option value="Reschedule"          {{ $delivStatus === 'Reschedule'          ? 'selected' : '' }}>Reschedule</option>
                                </select>
                            </div>
                            {{-- Location --}}
                            <div class="flex flex-col items-start min-w-[70px] flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-0.5">Location</label>
                                <select class="delivery_store_id border rounded px-3 py-3 text-xs w-full">
                                    <option value="" disabled>Select Location</option>
                                    @foreach ($stores as $storeItem)
                                        <option value="{{ $storeItem->id }}"
                                            {{ $op->delivery_store_id == $storeItem->id ? 'selected' : '' }}>
                                            {{ $storeItem->store_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Technician --}}
                            <div class="flex flex-col items-start min-w-[90px] flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-0.5">Technician</label>
                                <select class="delivery_by border rounded px-3 py-3 text-xs w-full">
                                    <option value="" disabled>Select Technician</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ $op->delivery_by == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>{{-- end delivery schedule --}}

                {{-- ── A. SOP ─────────────────────────────────────────────────── --}}
                <div>
                    <h3 class="text-sm font-semibold text-red-600 mb-2">Standard Operating Procedures (SOP)</h3>
                    <div class="border border-gray-200 rounded-xl px-4 divide-y divide-gray-100">

                        {{-- Customer Called --}}
                        <div class="sop-row">
                            <input type="checkbox"
                                class="dispatch-checkbox"
                                data-section="sop" data-key="customer_called"
                                {{ ($sop['customer_called'] ?? false) ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-red-600 {{ ($sop['customer_called'] ?? false) ? 'line-through text-gray-400' : '' }}">
                                Customer Called
                            </span>
                        </div>

                        {{-- Customer Texted --}}
                        <div class="sop-row">
                            <input type="checkbox"
                                class="dispatch-checkbox"
                                data-section="sop" data-key="customer_texted"
                                {{ ($sop['customer_texted'] ?? false) ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-red-600 {{ ($sop['customer_texted'] ?? false) ? 'line-through text-gray-400' : '' }}">
                                Customer Texted
                            </span>
                        </div>

                        {{-- Keys --}}
                        <div class="sop-row">
                            <input type="checkbox"
                                class="dispatch-checkbox"
                                data-section="sop" data-key="keys_checked"
                                {{ !empty($sop['keys']) ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-red-600">Keys</span>
                            <select class="result-select sop-result-select" data-key="keys">
                                <option value="">— Result —</option>
                                @foreach (['Full Set', '1 Key', 'N/A'] as $opt)
                                    <option value="{{ $opt }}" {{ ($sop['keys'] ?? '') === $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Fuel --}}
                        <div class="sop-row">
                            <input type="checkbox"
                                class="dispatch-checkbox"
                                data-section="sop" data-key="fuel_checked"
                                {{ !empty($sop['fuel']) ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-red-600">Fuel</span>
                            <select class="result-select sop-result-select" data-key="fuel">
                                <option value="">— Level —</option>
                                @foreach (['Full', '3/4', '1/2', '1/4', 'Empty'] as $opt)
                                    <option value="{{ $opt }}" {{ ($sop['fuel'] ?? '') === $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                {{-- ── B. Product Options ──────────────────────────────────────── --}}
                @if (!empty($productOpts))
                <div>
                    <h3 class="text-sm font-semibold text-red-600 mb-2">Product Options</h3>
                    <div class="border border-gray-200 rounded-xl px-4 divide-y divide-gray-100">
                        @foreach ($productOpts as $idx => $opt)
                        @php $optKey = 'opt_' . $idx; @endphp
                        <div class="option-row">
                            <input type="checkbox"
                                class="dispatch-checkbox"
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
                    <h3 class="text-sm font-semibold text-red-600 mb-2">Related Products</h3>
                    <div class="border border-gray-200 rounded-xl px-4 divide-y divide-gray-100">
                        @foreach ($relatedProds as $rel)
                        <div class="option-row">
                            <input type="checkbox"
                                class="dispatch-checkbox"
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
                            class="save-checklist-btn bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                            Save Checklist
                        </button>
                    </div>
                </div>

            </div>{{-- end px-5 py-5 --}}
        </div>{{-- end product card --}}
        @endforeach

    </div>{{-- end right column --}}

</div>{{-- end main grid --}}


{{-- ── Bottom row: Instructions + Completed By ───────────────────────────── --}}
<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Order / Delivery Instructions --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Order / Delivery Instructions</h2>
            <button id="addNoteBtn"
                class="flex items-center gap-1.5 text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg transition-colors">
                <x-heroicon-o-plus class="w-3.5 h-3.5" />
                Add Note
            </button>
        </div>
        <div id="notesList" class="px-5 py-4 min-h-[80px]">
            <p class="text-sm text-gray-400 italic">No notes available.</p>
        </div>
    </div>

    {{-- Completed By --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-red-600">Completed By</h2>
        </div>
        <div class="px-5 py-4">
            @php
                $completedById = $orderProduct->dispatch_checklist['completed_by'] ?? null;
            @endphp
            <select id="completedBySelect"
                class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm w-full focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">— Select Driver —</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}"
                        {{ $completedById == $emp->id ? 'selected' : '' }}>
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
    const scheduleBase = @json($scheduleRoute);

    // ── Schedule: wire up save-on-change per product card ──────────────────
    document.querySelectorAll('[data-dispatch-product]').forEach(function (card) {
        const productUid = card.dataset.dispatchProduct;
        const schedUrl   = scheduleBase
            .replace(':order_uid',   orderUid)
            .replace(':product_uid', productUid);

        function saveScheduleField(type, field, value) {
            const body = { _method: 'PUT', type: type };
            body[field] = value;
            return apiFetch(schedUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            })
            .then(res => {
                if (res && res.success !== false) {
                    window.notyf && notyf.success(res.message || 'Schedule updated.');
                } else {
                    window.notyf && notyf.error(res?.message || 'Failed to update schedule.');
                }
            })
            .catch(() => window.notyf && notyf.error('Failed to update schedule.'));
        }

        // Date (AirDatepicker fires 'change' on the input)
        const dateInput = card.querySelector('.delivery_date');
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                saveScheduleField('delivery', 'delivery_date', this.value);
            });
        }

        // Time (Flatpickr fires 'change' on the input)
        const timeInput = card.querySelector('.delivery_time');
        if (timeInput) {
            flatpickr(timeInput, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'h:i K',
                time_24hr: false,
                onClose: function (dates, dateStr) {
                    if (dateStr) saveScheduleField('delivery', 'delivery_time', dateStr);
                },
            });
        }

        // Transport mode (hidden input, Alpine fires 'change')
        const typeInput = card.querySelector('input[type=hidden].delivery_transport_mode');
        if (typeInput) {
            typeInput.addEventListener('change', function () {
                saveScheduleField('delivery', 'delivery_transport_mode', this.value);
            });
        }

        // Status
        const statusSel = card.querySelector('.delivery_status');
        if (statusSel) {
            statusSel.addEventListener('change', function () {
                saveScheduleField('delivery', 'delivery_status', this.value);
            });
        }

        // Location
        const locationSel = card.querySelector('.delivery_store_id');
        if (locationSel) {
            locationSel.addEventListener('change', function () {
                saveScheduleField('delivery', 'delivery_store_id', this.value);
            });
        }

        // Technician
        const techSel = card.querySelector('.delivery_by');
        if (techSel) {
            techSel.addEventListener('change', function () {
                saveScheduleField('delivery', 'delivery_by', this.value);
            });
        }
    });

    // ── Checklist: save per product card ───────────────────────────────────
    document.querySelectorAll('[data-dispatch-product]').forEach(function (card) {
        const productUid  = card.dataset.dispatchProduct;
        const saveUrl     = @json(route('admin.order-management.dispatch.checklist.save', ':uid'))
                              .replace(':uid', productUid);
        const saveStatus  = card.querySelector('.save-status');
        let   saveTimer   = null;

        // Build state from current DOM
        function buildState() {
            const state = { sop: {}, product_options: {}, related_products: {} };

            // SOP checkboxes
            card.querySelectorAll('.dispatch-checkbox[data-section="sop"]').forEach(cb => {
                state.sop[cb.dataset.key] = cb.checked;
            });

            // SOP result selects (keys, fuel)
            card.querySelectorAll('.sop-result-select').forEach(sel => {
                state.sop[sel.dataset.key] = sel.value || null;
            });

            // Product options
            card.querySelectorAll('.dispatch-checkbox[data-section="product_options"]').forEach(cb => {
                state.product_options[cb.dataset.key] = cb.checked;
            });

            // Related products
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
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(buildState()),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    flashSaved();
                    window.notyf && notyf.success('Checklist saved.');
                } else {
                    window.notyf && notyf.error('Failed to save checklist.');
                }
            })
            .catch(() => window.notyf && notyf.error('Failed to save checklist.'));
        }

        // Save button
        const saveBtn = card.querySelector('.save-checklist-btn');
        if (saveBtn) saveBtn.addEventListener('click', saveChecklist);

        // Strikethrough on checkbox toggle
        card.querySelectorAll('.dispatch-checkbox').forEach(function (cb) {
            cb.addEventListener('change', function () {
                const label   = this.closest('.sop-row, .option-row');
                const textEl  = label?.querySelector('span.text-sm');
                if (textEl) {
                    textEl.classList.toggle('line-through', this.checked);
                    textEl.classList.toggle('text-gray-400', this.checked);
                    textEl.classList.toggle('text-red-600', !this.checked);
                }
            });
        });

        // Auto-check keys/fuel checkbox when a result is selected
        card.querySelectorAll('.sop-result-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                const key = this.dataset.key;
                const cb  = card.querySelector(`.dispatch-checkbox[data-key="${key}_checked"]`);
                if (cb && this.value) cb.checked = true;
            });
        });
    });

    // ── Completed By ───────────────────────────────────────────────────────
    const completedBySel    = document.getElementById('completedBySelect');
    const completedByStatus = document.getElementById('completedByStatus');
    const primaryUid        = @json($orderProduct->unique_id);
    const primarySaveUrl    = @json(route('admin.order-management.dispatch.checklist.save', ':uid'))
                                .replace(':uid', primaryUid);

    if (completedBySel) {
        completedBySel.addEventListener('change', function () {
            fetch(primarySaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ completed_by: parseInt(this.value) || null }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && completedByStatus) {
                    completedByStatus.classList.remove('hidden');
                    setTimeout(() => completedByStatus.classList.add('hidden'), 2000);
                }
            });
        });
    }

    // ── Notes: load from order notes endpoint ──────────────────────────────
    const notesList = document.getElementById('notesList');
    const notesUrl  = @json(route('admin.order-management.orders.notes.index', ':uid'))
                        .replace(':uid', orderUid);

    function loadNotes() {
        if (!notesList || !orderUid) return;
        apiFetch(notesUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        }).then(res => {
            if (res && res.success && res.html) {
                notesList.innerHTML = res.html;
            }
        }).catch(() => {});
    }

    loadNotes();

    const addNoteBtn = document.getElementById('addNoteBtn');
    if (addNoteBtn) {
        addNoteBtn.addEventListener('click', function () {
            // Link to full order edit to add notes (dispatch is view-only for notes)
            window.open(@json(route('admin.order-management.orders.edit', ':uid')).replace(':uid', orderUid), '_blank');
        });
    }
});
</script>
@endpush

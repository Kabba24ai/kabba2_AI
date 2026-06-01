@extends('admin.layouts.app')

@section('title', 'Dispatch Detail')

@push('css')
<style>
    .checklist-card { @apply bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5; }
    .checklist-item { @apply flex items-center gap-3 py-2.5 border-b border-gray-100 last:border-0; }
    .check-box {
        width: 1.25rem; height: 1.25rem;
        border-radius: 0.25rem;
        border: 2px solid #d1d5db;
        flex-shrink: 0;
        cursor: pointer;
        accent-color: #16a34a;
    }
    .contact-btn {
        @apply px-4 py-2 rounded-lg text-sm font-medium border transition-all cursor-pointer select-none;
    }
    .contact-btn.active { @apply bg-blue-600 text-white border-blue-600; }
    .contact-btn.inactive { @apply bg-white text-gray-700 border-gray-300 hover:border-blue-400; }
</style>
@endpush

@section('content')

@include('flash::message')

{{-- ===== PAGE HEADER ===== --}}
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
                Order <span class="font-semibold text-gray-700">#{{ $orderProduct->order?->order_number }}</span>
                &mdash; {{ $orderProduct->product_name }}
            </p>
        </div>
    </div>
    <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order?->unique_id) }}"
        class="text-sm text-blue-600 hover:underline flex items-center gap-1">
        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
        View Full Order
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ===== LEFT COLUMN — CHECKLIST ===== --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- ──── 1. CUSTOMER CONTACT ──── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <x-heroicon-o-phone class="w-4 h-4 text-blue-500" />
                Customer Called
                <span id="contact-badge" class="ml-auto text-xs font-medium px-2 py-0.5 rounded-full
                    {{ ($checklist['customer_contact'] ?? null) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ ($checklist['customer_contact'] ?? null) ? '✓ Logged' : 'Not logged' }}
                </span>
            </h2>
            <div class="flex flex-wrap gap-2" id="contact-btns">
                @php
                    $contactOptions = [
                        'spoke' => 'Spoke with Customer',
                        'vm'    => 'No Answer, Left VM',
                        'text'  => 'No Answer, Sent Text',
                    ];
                    $currentContact = $checklist['customer_contact'] ?? null;
                @endphp
                @foreach ($contactOptions as $val => $label)
                    <button type="button"
                        class="contact-btn {{ $currentContact === $val ? 'active' : 'inactive' }}"
                        data-contact="{{ $val }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ──── 2. OPTIONS / ADD-ONS ──── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-orange-500" />
                Options &amp; Add-Ons
                <span class="text-xs text-gray-400 font-normal ml-1">(verify all items are loaded)</span>
            </h2>
            @php
                $optChecks = $checklist['options'] ?? [];
                $dispatchOptions = [
                    'prepaid_fuel'     => 'Prepaid Fuel',
                    'prepaid_cleaning' => 'Prepaid Cleaning',
                ];
            @endphp
            <div class="divide-y divide-gray-100">
                @foreach ($dispatchOptions as $key => $label)
                    <label class="flex items-center gap-3 py-3 cursor-pointer group">
                        <input type="checkbox"
                            class="dispatch-check w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500"
                            data-section="options"
                            data-key="{{ $key }}"
                            {{ ($optChecks[$key] ?? false) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-800 group-hover:text-gray-900 {{ ($optChecks[$key] ?? false) ? 'line-through text-gray-400' : '' }}">
                            {{ $label }}
                        </span>
                        @if ($optChecks[$key] ?? false)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-green-500 ml-auto" />
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ──── 3. ALL PRODUCTS IN ORDER ──── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <x-heroicon-o-cube class="w-4 h-4 text-purple-500" />
                Products in Order
                <span class="text-xs text-gray-400 font-normal ml-1">(confirm all items are on the truck)</span>
            </h2>
            @php $productChecks = $checklist['products'] ?? []; @endphp
            <div class="divide-y divide-gray-100">
                @forelse ($orderProducts as $op)
                    <label class="flex items-center gap-3 py-3 cursor-pointer group">
                        <input type="checkbox"
                            class="dispatch-check w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500"
                            data-section="products"
                            data-key="{{ $op->unique_id }}"
                            {{ ($productChecks[$op->unique_id] ?? false) ? 'checked' : '' }}>
                        <div class="flex-1">
                            <span class="text-sm text-gray-800 {{ ($productChecks[$op->unique_id] ?? false) ? 'line-through text-gray-400' : '' }}">
                                {{ $op->product_name }}
                            </span>
                            <div class="text-xs text-gray-400">Qty: {{ $op->quantity }}</div>
                        </div>
                        @if ($productChecks[$op->unique_id] ?? false)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-green-500" />
                        @endif
                    </label>
                @empty
                    <p class="text-sm text-gray-400 italic py-2">No products found.</p>
                @endforelse
            </div>
        </div>

        {{-- ──── 4. DELIVERY INSTRUCTIONS ──── --}}
        @php
            $deliveryNotes = trim($orderProduct->delivery_notes ?? '');
            $pickupNotes   = trim($orderProduct->pickup_notes ?? '');
            $orderNote     = trim($orderProduct->order?->order_note ?? '');
        @endphp
        @if ($deliveryNotes || $pickupNotes || $orderNote)
        <div class="bg-amber-50 rounded-xl border border-amber-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-amber-800 mb-3 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-amber-500" />
                Delivery Instructions
            </h2>
            @if ($orderNote)
                <div class="mb-3">
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">Order Note</p>
                    <p class="text-sm text-amber-900 whitespace-pre-wrap">{{ $orderNote }}</p>
                </div>
            @endif
            @if ($deliveryNotes)
                <div class="mb-3">
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">Delivery Notes</p>
                    <p class="text-sm text-amber-900 whitespace-pre-wrap">{{ $deliveryNotes }}</p>
                </div>
            @endif
            @if ($pickupNotes)
                <div>
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">Return / Pickup Notes</p>
                    <p class="text-sm text-amber-900 whitespace-pre-wrap">{{ $pickupNotes }}</p>
                </div>
            @endif
        </div>
        @else
        <div class="bg-white rounded-xl border border-dashed border-gray-200 p-5 text-center text-sm text-gray-400">
            <x-heroicon-o-document-text class="w-5 h-5 mx-auto mb-1 text-gray-300" />
            No delivery instructions on this order.
        </div>
        @endif

    </div>{{-- end left column --}}

    {{-- ===== RIGHT COLUMN — SCHEDULE + CUSTOMER INFO ===== --}}
    <div class="space-y-5">

        {{-- ──── Customer Info ──── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <x-heroicon-o-user class="w-4 h-4 text-gray-500" />
                Customer
            </h2>
            @php $addr = $orderProduct->order?->shippingAddress; @endphp
            <div class="space-y-1.5 text-sm">
                <div class="font-semibold text-gray-800">{{ $orderProduct->order?->customer_name ?? '-' }}</div>
                @if ($orderProduct->order?->company_name)
                    <div class="text-gray-500 text-xs">{{ $orderProduct->order->company_name }}</div>
                @endif
                @if ($addr?->phone)
                    <a href="tel:{{ preg_replace('/\D/', '', $addr->phone) }}"
                        class="flex items-center gap-1.5 text-blue-600 hover:underline text-xs">
                        <x-heroicon-o-phone class="w-3.5 h-3.5" />
                        {{ $addr->phone }}
                    </a>
                @endif
                @if ($addr)
                    <div class="text-xs text-gray-500 leading-relaxed mt-1">
                        {{ $addr->full_address }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ──── 5. DELIVERY SCHEDULE (replicated from order edit) ──── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <x-heroicon-o-arrow-right-circle class="w-4 h-4 text-blue-500" />
                Delivery Schedule
            </h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Date</span>
                    <span class="font-medium text-gray-800">
                        {{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, Y') : 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Time</span>
                    <span class="font-medium text-gray-800">
                        {{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Status</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $orderProduct->delivery_status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $orderProduct->delivery_status ?? 'Pending' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Location</span>
                    <span class="font-medium text-gray-800">{{ $orderProduct->deliveryStore?->store_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Type</span>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-700">
                        @if ($orderProduct->delivery_transport_mode === 'Truck')
                            <x-heroicon-o-truck class="w-3.5 h-3.5 text-blue-500" /> Truck
                        @else
                            <x-heroicon-o-building-storefront class="w-3.5 h-3.5 text-gray-400" /> Store
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Driver</span>
                    <span class="font-medium text-gray-800">{{ $orderProduct->deliveryEmployee?->full_name ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- ──── 5. RETURN SCHEDULE (replicated from order edit) ──── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <x-heroicon-o-arrow-left-circle class="w-4 h-4 text-purple-500" />
                Return Schedule
            </h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Date</span>
                    <span class="font-medium text-gray-800">
                        {{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, Y') : 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Time</span>
                    <span class="font-medium text-gray-800">
                        {{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Status</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $orderProduct->pickup_status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $orderProduct->pickup_status ?? 'Pending' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Location</span>
                    <span class="font-medium text-gray-800">{{ $orderProduct->pickupStore?->store_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Type</span>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-700">
                        @if ($orderProduct->pickup_transport_mode === 'Truck')
                            <x-heroicon-o-truck class="w-3.5 h-3.5 text-purple-500" /> Truck
                        @else
                            <x-heroicon-o-building-storefront class="w-3.5 h-3.5 text-gray-400" /> Store
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Driver</span>
                    <span class="font-medium text-gray-800">{{ $orderProduct->pickupEmployee?->full_name ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- ──── Save indicator ──── --}}
        <div id="save-status"
            class="hidden text-center text-xs text-green-600 font-semibold py-2">
            ✓ Checklist saved
        </div>

        @if ($checklist['updated_at'] ?? null)
        <p class="text-center text-xs text-gray-400">
            Last saved: {{ \Carbon\Carbon::parse($checklist['updated_at'])->format('M d, Y g:i A') }}
        </p>
        @endif

    </div>{{-- end right column --}}

</div>{{-- end grid --}}

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const opUid      = '{{ $orderProduct->unique_id }}';
    const saveUrl    = '{{ route('admin.order-management.dispatch.checklist.save', $orderProduct->unique_id) }}';
    const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content;
    const saveStatus = document.getElementById('save-status');

    // ─── Checklist state (mirrors server-side) ───
    let state = {
        customer_contact: '{{ $checklist['customer_contact'] ?? '' }}' || null,
        options: {
            prepaid_fuel:     {{ ($checklist['options']['prepaid_fuel'] ?? false) ? 'true' : 'false' }},
            prepaid_cleaning: {{ ($checklist['options']['prepaid_cleaning'] ?? false) ? 'true' : 'false' }},
        },
        products: @json($checklist['products'] ?? []),
    };

    let saveTimer = null;

    function flashSaved() {
        clearTimeout(saveTimer);
        saveStatus.classList.remove('hidden');
        saveTimer = setTimeout(() => saveStatus.classList.add('hidden'), 2500);
    }

    function save() {
        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(state),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) flashSaved();
        });
    }

    // ─── Customer Contact buttons ───
    document.querySelectorAll('.contact-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const val = this.dataset.contact;
            // Toggle off if already selected
            if (state.customer_contact === val) {
                state.customer_contact = null;
                document.querySelectorAll('.contact-btn').forEach(b => {
                    b.classList.remove('active');
                    b.classList.add('inactive');
                });
            } else {
                state.customer_contact = val;
                document.querySelectorAll('.contact-btn').forEach(b => {
                    b.classList.toggle('active',   b.dataset.contact === val);
                    b.classList.toggle('inactive', b.dataset.contact !== val);
                });
            }
            // Update badge
            const badge = document.getElementById('contact-badge');
            if (state.customer_contact) {
                badge.textContent = '✓ Logged';
                badge.className = 'ml-auto text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700';
            } else {
                badge.textContent = 'Not logged';
                badge.className = 'ml-auto text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500';
            }
            save();
        });
    });

    // ─── Checkbox items (options + products) ───
    document.querySelectorAll('.dispatch-check').forEach(cb => {
        cb.addEventListener('change', function () {
            const section = this.dataset.section;
            const key     = this.dataset.key;
            const checked = this.checked;

            if (section === 'options') {
                state.options[key] = checked;
            } else if (section === 'products') {
                state.products[key] = checked;
            }

            // Toggle strikethrough on label text
            const label = this.closest('label');
            const textEl = label?.querySelector('span.text-sm');
            if (textEl) {
                textEl.classList.toggle('line-through', checked);
                textEl.classList.toggle('text-gray-400', checked);
            }
            const icon = label?.querySelector('.heroicon-o-check-circle, [class*="check-circle"]');
            // Show/hide check icon (simpler: just add/remove a hidden span)
            let checkIcon = label?.querySelector('.inline-check-icon');
            if (!checkIcon) {
                checkIcon = document.createElement('span');
                checkIcon.className = 'inline-check-icon text-green-500 ml-auto text-sm';
                checkIcon.textContent = '✓';
                label?.appendChild(checkIcon);
            }
            checkIcon.classList.toggle('hidden', !checked);

            save();
        });

        // Initialize ✓ icons on load
        if (cb.checked) {
            const label = cb.closest('label');
            let checkIcon = label?.querySelector('.inline-check-icon');
            if (!checkIcon) {
                checkIcon = document.createElement('span');
                checkIcon.className = 'inline-check-icon text-green-500 ml-auto text-sm';
                checkIcon.textContent = '✓';
                label?.appendChild(checkIcon);
            }
        }
    });
});
</script>
@endpush

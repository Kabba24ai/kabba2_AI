@extends('admin.layouts.app')

@section('title', 'New Warranty Case')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-purple-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
        $oldPath = old('path', 'external');
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-6 h-6 text-purple-600" />
                    New Warranty Case
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200">Intake Stage</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Open the claim and its linked Service Ticket. Diagnosis, submission, and decisions continue on the case page.
            </p>
        </div>
        <a href="{{ route('admin.warranty.claims.index') }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Warranty Claims
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-700 mb-1">Please correct the following:</p>
            <ul class="text-sm text-red-600 list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.warranty.claims.store') }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- ═══════════ MAIN COLUMN ═══════════ --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- ===== 1. Warranty Path ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-heroicon-o-shield-check class="w-4 h-4" />
                        </span>
                        1 · Warranty Path
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">Who owns the machine decides who pays, who is invoiced, and whether a diagnostic fee applies.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="path" value="internal" class="peer sr-only wc-path" @checked($oldPath === 'internal')>
                            <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 transition hover:border-purple-300
                                peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:ring-2 peer-checked:ring-purple-200">
                                <p class="text-sm font-semibold text-gray-900">Internal Equipment Warranty</p>
                                <p class="text-xs text-gray-600 mt-1">Our fleet unit. No diagnostic fee — warranty recovery offsets our own repair cost.</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="path" value="external" class="peer sr-only wc-path" @checked($oldPath === 'external')>
                            <div class="h-full rounded-xl border-2 border-gray-200 bg-white p-4 transition hover:border-purple-300
                                peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:ring-2 peer-checked:ring-purple-200">
                                <p class="text-sm font-semibold text-gray-900">External Customer Warranty</p>
                                <p class="text-xs text-gray-600 mt-1">Customer-owned machine. Diagnostic fee applies; the customer decides on non-covered work.</p>
                            </div>
                        </label>
                    </div>
                    @error('path')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div id="wc-internal-owner">
                            <label class="{{ $labelClass }} required">Fleet Equipment</label>
                            <select name="equipment_id" id="wc-equipment" class="{{ $inputClass }}">
                                <option value="">— Select unit —</option>
                                @foreach ($equipmentOptions as $unit)
                                    <option value="{{ $unit['id'] }}" data-brand="{{ $unit['brand'] }}" data-serial="{{ $unit['serial'] }}"
                                        @selected((int) old('equipment_id') === $unit['id'])>{{ $unit['label'] }}</option>
                                @endforeach
                            </select>
                            @error('equipment_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div id="wc-external-owner">
                            <label class="{{ $labelClass }} required">Customer</label>
                            <select name="customer_id" id="wc-customer" class="{{ $inputClass }}">
                                <option value="">Search name, company, phone…</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((int) old('customer_id') === $customer->id)>
                                        {{ $customer->first_name }} {{ $customer->last_name }}{{ $customer->company_name ? ' — ' . $customer->company_name : '' }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Existing CRM customers only.</p>
                            @error('customer_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- ===== 2. Equipment Identity ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <x-heroicon-o-tag class="w-4 h-4" />
                        </span>
                        2 · Equipment Identity
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">Exactly what the manufacturer will ask for — serial numbers are the claim's fingerprint.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="{{ $labelClass }} required">Manufacturer</label>
                            <input type="text" name="manufacturer" id="wc-manufacturer" value="{{ old('manufacturer') }}" required class="{{ $inputClass }}">
                            @error('manufacturer')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }} required">Model</label>
                            <input type="text" name="model" value="{{ old('model') }}" required class="{{ $inputClass }}">
                            @error('model')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }} required">Serial Number</label>
                            <input type="text" name="serial_number" id="wc-serial" value="{{ old('serial_number') }}" required class="{{ $inputClass }}">
                            @error('serial_number')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Engine Serial Number</label>
                            <input type="text" name="engine_serial_number" value="{{ old('engine_serial_number') }}" class="{{ $inputClass }}" placeholder="If applicable…">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Hour Meter?</label>
                            <select name="has_hour_meter" id="wc-has-hours" class="{{ $inputClass }}">
                                <option value="0" @selected(!old('has_hour_meter'))>No</option>
                                <option value="1" @selected(old('has_hour_meter'))>Yes</option>
                            </select>
                        </div>
                        <div id="wc-hours-wrap">
                            <label class="{{ $labelClass }} required">Hours</label>
                            <input type="number" name="hours" min="0" value="{{ old('hours') }}" class="{{ $inputClass }}">
                            @error('hours')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- ===== 3. Ownership & Warranty Information ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-4 h-4" />
                        </span>
                        3 · Ownership &amp; Warranty Information
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500 border border-gray-200">Optional</span>
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">Strengthens the claim — add what's available now; the rest can follow before submission.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="{{ $labelClass }}">Purchase Date</label>
                            <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Selling Dealer</label>
                            <input type="text" name="selling_dealer" value="{{ old('selling_dealer') }}" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Warranty Registration #</label>
                            <input type="text" name="warranty_registration_number" value="{{ old('warranty_registration_number') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Proof-of-purchase upload arrives with the submission phase.</p>
                </div>

                {{-- ===== 4. Complaint ===== --}}
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                        <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </span>
                        4 · Complaint
                    </h2>
                    <div>
                        <label class="{{ $labelClass }} required">Complaint</label>
                        <textarea name="complaint" rows="4" required class="{{ $inputClass }}"
                            placeholder="What is the machine doing (or not doing)? This becomes the linked Service Ticket's complaint…">{{ old('complaint') }}</textarea>
                        @error('complaint')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-4">
                        <label class="{{ $labelClass }}">Internal Notes</label>
                        <textarea name="internal_notes" rows="2" class="{{ $inputClass }}"
                            placeholder="Internal notes (not customer-facing)…">{{ old('internal_notes') }}</textarea>
                    </div>
                </div>

                {{-- ===== 5. Diagnostic Fee (external only) ===== --}}
                <div id="wc-fee-card" class="bg-white rounded-xl border border-gray-200 border-l-[3px] border-l-teal-400 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
                        <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                            <x-heroicon-o-banknotes class="w-4 h-4" />
                        </span>
                        5 · Diagnostic Fee
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-teal-100 text-teal-700 border border-teal-200">External only</span>
                    </h2>
                    <p class="text-xs text-gray-400 mb-4">Recorded here — collected through your normal payment channels. Waivers happen on the case page.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="{{ $labelClass }}">Fee Amount</label>
                            <input type="number" step="0.01" min="0" name="diagnostic_fee_amount"
                                value="{{ old('diagnostic_fee_amount', number_format($defaultFee, 2, '.', '')) }}" class="{{ $inputClass }}">
                            @error('diagnostic_fee_amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Tax</label>
                            <select name="diagnostic_fee_taxable" class="{{ $inputClass }}">
                                <option value="1" @selected(old('diagnostic_fee_taxable', '1') === '1')>Taxable</option>
                                <option value="0" @selected(old('diagnostic_fee_taxable') === '0')>Non-taxable</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="diagnostic_fee_collected" value="1" @checked(old('diagnostic_fee_collected'))
                                    class="text-teal-600 rounded focus:ring-teal-500">
                                Collected at intake
                            </label>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">
                        Refund policy: the fee is credited toward the repair when the claim is approved or the customer authorizes
                        paid repair; it is retained if the customer declines.
                    </p>
                </div>

                <div class="flex justify-end items-center gap-3">
                    <span class="text-xs text-gray-400 mr-auto">Creating the case also opens its linked Service Ticket.</span>
                    <a href="{{ route('admin.warranty.claims.index') }}"
                        class="px-6 py-3 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-3 rounded-lg font-medium text-sm bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">
                        Create Warranty Case
                    </button>
                </div>
            </div>

            {{-- ═══════════ RIGHT SIDEBAR ═══════════ --}}
            <div class="space-y-6 lg:sticky lg:top-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5 mb-3">
                        <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-gray-400" />
                        Intake Summary
                    </h2>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-3"><dt class="text-xs text-gray-400 pt-0.5">Warranty Path</dt><dd id="wc-sum-path" class="text-xs font-medium text-gray-700 text-right">External Customer</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-xs text-gray-400 pt-0.5">Manufacturer</dt><dd id="wc-sum-mfr" class="text-xs font-medium text-gray-400 text-right">Not entered</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-xs text-gray-400 pt-0.5">Equipment</dt><dd id="wc-sum-equipment" class="text-xs font-medium text-gray-400 text-right">Not entered</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-xs text-gray-400 pt-0.5">Owner</dt><dd id="wc-sum-owner" class="text-xs font-medium text-gray-400 text-right">Not selected</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-xs text-gray-400 pt-0.5">Diagnostic Fee</dt><dd id="wc-sum-fee" class="text-xs font-medium text-gray-700 text-right">—</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-xs text-gray-400 pt-0.5">Service Ticket</dt><dd class="text-xs font-medium text-gray-700 text-right">Created on save</dd></div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5 mb-3">
                        <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-gray-400" />
                        What happens next
                    </h2>
                    <ol class="space-y-3">
                        @foreach ([
                            'Service Ticket created & linked',
                            'Technician performs diagnosis',
                            'Warranty submitted to manufacturer',
                            'OEM decision recorded',
                            'Repair authorized & reimbursed',
                        ] as $step)
                            <li class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-purple-50 border border-purple-200 text-purple-600 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">{{ $loop->iteration }}</span>
                                <span class="text-xs text-gray-600 leading-5">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="text-[11px] text-gray-400 mt-4 pt-3 border-t border-gray-100">
                        The Warranty Case authorizes work — the linked Service Ticket performs it.
                    </p>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const internalOwner = document.getElementById('wc-internal-owner');
    const externalOwner = document.getElementById('wc-external-owner');
    const feeCard       = document.getElementById('wc-fee-card');
    const equipment     = document.getElementById('wc-equipment');
    const customer      = document.getElementById('wc-customer');
    const manufacturer  = document.getElementById('wc-manufacturer');
    const serial        = document.getElementById('wc-serial');
    const hasHours      = document.getElementById('wc-has-hours');
    const hoursWrap     = document.getElementById('wc-hours-wrap');
    const sum = {
        path:      document.getElementById('wc-sum-path'),
        mfr:       document.getElementById('wc-sum-mfr'),
        equipment: document.getElementById('wc-sum-equipment'),
        owner:     document.getElementById('wc-sum-owner'),
        fee:       document.getElementById('wc-sum-fee'),
    };
    let identityAutofilled = false;

    function currentPath() {
        const checked = document.querySelector('.wc-path:checked');
        return checked ? checked.value : 'external';
    }

    function syncPath() {
        const external = currentPath() === 'external';
        internalOwner.classList.toggle('hidden', external);
        externalOwner.classList.toggle('hidden', !external);
        feeCard.classList.toggle('hidden', !external);
        equipment.disabled = external;
        customer.disabled = !external;
        syncSummary();
    }

    // Internal path: fleet unit autofills manufacturer + serial (editable)
    function syncEquipmentIdentity() {
        const option = equipment.options[equipment.selectedIndex];
        if (!option || !option.value) return;
        if (option.dataset.brand && (!manufacturer.value || identityAutofilled)) {
            manufacturer.value = option.dataset.brand;
            identityAutofilled = true;
        }
        if (option.dataset.serial && (!serial.value || identityAutofilled)) {
            serial.value = option.dataset.serial;
        }
        syncSummary();
    }

    function syncHours() {
        hoursWrap.classList.toggle('hidden', hasHours.value !== '1');
    }

    function setSummary(el, value, empty) {
        el.textContent = value || empty;
        el.classList.toggle('text-gray-400', !value);
        el.classList.toggle('text-gray-700', !!value);
    }

    function syncSummary() {
        const external = currentPath() === 'external';
        setSummary(sum.path, external ? 'External Customer' : 'Internal Equipment', '');
        setSummary(sum.mfr, manufacturer.value.trim(), 'Not entered');

        const model = document.querySelector('input[name="model"]').value.trim();
        const sn    = serial.value.trim();
        setSummary(sum.equipment, [model, sn].filter(Boolean).join(' · '), 'Not entered');

        if (external) {
            const option = customer.options[customer.selectedIndex];
            setSummary(sum.owner, customer.value ? option.textContent.trim() : '', 'Not selected');
        } else {
            const option = equipment.options[equipment.selectedIndex];
            setSummary(sum.owner, equipment.value ? 'Internal · ' + option.textContent.trim() : '', 'Not selected');
        }

        if (!external) {
            setSummary(sum.fee, 'N/A — internal', '');
        } else {
            const amount    = document.querySelector('input[name="diagnostic_fee_amount"]').value;
            const collected = document.querySelector('input[name="diagnostic_fee_collected"]').checked;
            setSummary(sum.fee, (collected ? 'Collected' : 'Unpaid') + (amount ? ' · $' + Number(amount).toFixed(2) : ''), '—');
        }
    }

    document.querySelectorAll('.wc-path').forEach(el => el.addEventListener('change', syncPath));
    equipment.addEventListener('change', syncEquipmentIdentity);
    hasHours.addEventListener('change', function () { syncHours(); });
    ['input', 'change'].forEach(function (evt) {
        document.querySelectorAll('#wc-manufacturer, input[name="model"], #wc-serial, #wc-customer, input[name="diagnostic_fee_amount"], input[name="diagnostic_fee_collected"]')
            .forEach(el => el.addEventListener(evt, syncSummary));
    });

    new Choices(customer, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Type a name, company, or phone…',
    });
    new Choices(equipment, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        searchPlaceholderValue: 'Type a unit name or number…',
    });

    syncHours();
    syncPath();
});
</script>
@endpush

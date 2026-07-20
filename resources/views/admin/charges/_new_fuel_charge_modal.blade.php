{{-- Shared New Fuel Charge modal (Dashboard V2) — included by the
     Dashboard, the Fuel Charge Workspace, and the CRM customer page.
     ONE Blade file, ONE JS implementation, ONE normalized payload posted
     to the ONE canonical endpoint (admin.dashboard.fuel-charge.store →
     ChargeService::createManualCharge). Launchers differ only in the
     contextual defaults they pass to NewFuelCharge.open().

     Include vars:
       $users            active users (attribution select)
       $fuelNotePresets  optional preset collection (default: none)
       $nfcContext       'dashboard' | 'fuel_workspace' | 'crm'

     Billing Engine Commonization: the completion choice is UNIVERSAL —
     Cancel / Save Charge — Pay Later / Save Charge — Continue to Payment —
     identical from every launcher. "Continue to Payment" hands the shared
     creation-response contract to window.BillingPayment (the one Billing
     Engine payment component); this modal owns no payment behavior.
--}}
@php
    $fuelNotePresets = $fuelNotePresets ?? collect();
    $nfcContext      = $nfcContext ?? 'dashboard';
    $nfcTaxRate      = \App\Services\ChargeTaxCalculator::currentRate();
    $nfcTaxPct       = rtrim(rtrim(number_format($nfcTaxRate * 100, 2, '.', ''), '0'), '.');
@endphp

<div id="nfc-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">New Fuel Charge</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-nfc-close>&times;</button>
        </div>

        {{-- Order (optional, searchable) --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Order <span class="text-gray-400 font-normal">— optional</span></label>
            <div id="nfc-order-selected" class="hidden items-center justify-between gap-2 border border-blue-200 bg-blue-50/60 rounded-md px-3 py-2 text-sm">
                <span id="nfc-order-label" class="text-gray-800"></span>
                <button type="button" id="nfc-order-clear" class="text-xs text-blue-600 hover:underline shrink-0">Change</button>
            </div>
            <div id="nfc-order-search-wrap" class="relative">
                <input type="text" id="nfc-order-search" autocomplete="off"
                       placeholder="Search by order # or customer…"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                <div id="nfc-order-results" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
            </div>
        </div>

        {{-- Customer (derived from order, or searched when no order) --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500" id="nfc-customer-required">*</span></label>
            <div id="nfc-customer-selected" class="hidden items-center justify-between gap-2 border border-gray-200 bg-gray-50 rounded-md px-3 py-2 text-sm">
                <span id="nfc-customer-label" class="text-gray-800"></span>
                <button type="button" id="nfc-customer-clear" class="text-xs text-blue-600 hover:underline shrink-0">Change</button>
            </div>
            <div id="nfc-customer-search-wrap" class="relative">
                <input type="text" id="nfc-customer-search" autocomplete="off"
                       placeholder="Search customers…"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                <div id="nfc-customer-results" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto"></div>
            </div>
        </div>

        {{-- Amount + tax — the Add Extension Charge pattern, now the
             Billing Engine standard. Taxable is ALWAYS the default; No Tax
             requires an explicit employee action. --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Base Amount <span class="text-red-500">*</span></label>
            <div class="flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-blue-500">
                <span class="text-gray-500 text-sm mr-1">$</span>
                <input type="number" step="0.01" min="0.01" id="nfc-amount"
                       class="flex-1 text-sm focus:outline-none" placeholder="0.00">
            </div>
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Sales Tax</label>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="nfc-tax" value="add" checked class="accent-teal-500">
                    <span class="text-sm text-gray-700">Add {{ $nfcTaxPct }}% Tax</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="nfc-tax" value="free" class="accent-teal-500">
                    <span class="text-sm text-gray-700">No Tax</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="nfc-tax" value="reverse" class="accent-teal-500">
                    <span class="text-sm text-gray-700">Tax Included in Amount</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <x-admin.billing.charge-summary id-prefix="nfc" :tax-percentage="$nfcTaxPct" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea id="nfc-notes" rows="2" maxlength="500"
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                      placeholder="Gallons, fuel level, context…"></textarea>
            @if ($fuelNotePresets->isNotEmpty())
                <select id="nfc-preset-select" class="mt-2 w-full border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-600">
                    <option value="">Insert preset note…</option>
                    @foreach ($fuelNotePresets as $preset)
                        <option value="{{ $preset->label }}">{{ $preset->label }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Responsible Person <span class="text-red-500">*</span></label>
            <select id="nfc-user" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected($u->id === auth()->id())>{{ $u->first_name }} {{ $u->last_name }}</option>
                @endforeach
            </select>
        </div>

        <div id="nfc-error" class="hidden mb-3 text-sm text-red-600"></div>

        {{-- Deliberate two-line, centered button labels — the long labels
             wrap unpredictably as one line at this modal width. --}}
        <div class="flex justify-end items-stretch gap-2">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-nfc-close>Cancel</button>
            <button type="button" id="nfc-save"
                    class="px-4 py-1.5 text-sm rounded-md border border-orange-500 text-orange-600 hover:bg-orange-50 text-center leading-snug">
                <span class="block">Save Charge</span>
                <span class="block">Pay Later</span>
            </button>
            <button type="button" id="nfc-save-pay"
                    class="px-4 py-1.5 text-sm rounded-md bg-orange-500 text-white hover:bg-orange-600 text-center leading-snug">
                <span class="block">Save Charge</span>
                <span class="block">Continue to Payment</span>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const CONTEXT = @json($nfcContext);
    const TAX_RATE = Number(@json($nfcTaxRate)) || 0;
    const URLS = {
        store:     @json(route('admin.dashboard.fuel-charge.store')),
        orders:    @json(route('admin.dashboard.charge-modal.orders')),
        customers: @json(route('admin.dashboard.charge-modal.customers')),
    };

    const $ = (id) => document.getElementById(id);
    const state = { orderId: null, customerId: null, customerLocked: false, orderLocked: false };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // UI quality standard: the literal text "null"/"undefined" must never
    // render — absent values are simply omitted.
    function joinParts(parts, sep = ' · ') {
        return parts.filter((p) => p !== null && p !== undefined && p !== '' && p !== 'null').join(sep);
    }

    function show(el, on) { el.classList.toggle('hidden', !on); el.classList.toggle('flex', on && el.id.endsWith('-selected')); }

    function setOrder(order) {
        state.orderId = order ? order.id : null;
        show($('nfc-order-selected'), !!order);
        show($('nfc-order-search-wrap'), !order && !state.orderLocked);
        $('nfc-order-clear').classList.toggle('hidden', state.orderLocked);
        if (order) {
            $('nfc-order-label').textContent =
                joinParts([`Order ${order.order_number}`, order.customer_name, order.order_date]);
            // Customer is DERIVED from the order — locked, never mixable.
            setCustomer({ id: order.customer_id, name: order.customer_name }, true);
        } else if (!state.customerLocked) {
            setCustomer(null, false);
        }
    }

    function setCustomer(customer, locked) {
        state.customerId = customer ? customer.id : null;
        const isSet = !!customer;
        show($('nfc-customer-selected'), isSet);
        show($('nfc-customer-search-wrap'), !isSet);
        if (isSet) {
            $('nfc-customer-label').textContent = customer.name + (locked ? '  (from order)' : '');
        }
        $('nfc-customer-clear').classList.toggle('hidden', !!locked || state.customerLocked);
    }

    function typeahead(inputId, resultsId, url, renderRow, onPick) {
        const input = $(inputId), results = $(resultsId);
        let t;
        input.addEventListener('input', () => {
            clearTimeout(t);
            const q = input.value.trim();
            if (q.length < 2) { results.classList.add('hidden'); return; }
            t = setTimeout(async () => {
                const res = await fetch(`${url}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                results.innerHTML = (data.results || []).map(renderRow).join('')
                    || '<div class="px-3 py-2 text-sm text-gray-400">No matches</div>';
                results.classList.remove('hidden');
                results.querySelectorAll('[data-pick]').forEach((row) => {
                    row.addEventListener('click', () => {
                        onPick(JSON.parse(row.dataset.pick));
                        results.classList.add('hidden');
                        input.value = '';
                    });
                });
            }, 300);
        });
        document.addEventListener('click', (e) => {
            if (!results.contains(e.target) && e.target !== input) results.classList.add('hidden');
        });
    }

    typeahead('nfc-order-search', 'nfc-order-results', URLS.orders,
        (o) => `<div data-pick='${esc(JSON.stringify(o))}' class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                    <span class="font-medium">${esc(joinParts(['Order', o.order_number], ' '))}</span>
                    <span class="text-gray-600">${esc(joinParts(['', o.customer_name]))}</span>
                    <span class="text-gray-400">${esc(joinParts(['', o.order_date, o.status]))}</span>
                </div>`,
        (o) => setOrder(o));

    typeahead('nfc-customer-search', 'nfc-customer-results', URLS.customers,
        (c) => `<div data-pick='${esc(JSON.stringify(c))}' class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                    <span class="font-medium">${esc(c.name)}</span>
                    <span class="text-gray-400">${esc(joinParts(['', c.company, c.phone]))}</span>
                </div>`,
        (c) => setCustomer(c, false));

    function taxTreatment() {
        return document.querySelector('input[name="nfc-tax"]:checked')?.value || 'add';
    }
    function refreshSummary() {
        window.BillingSummary.update('nfc', $('nfc-amount').value, taxTreatment(), TAX_RATE);
    }
    $('nfc-amount').addEventListener('input', refreshSummary);
    document.querySelectorAll('input[name="nfc-tax"]').forEach((r) => r.addEventListener('change', refreshSummary));

    $('nfc-order-clear').addEventListener('click', () => setOrder(null));
    $('nfc-customer-clear').addEventListener('click', () => setCustomer(null, false));
    const presetSelect = $('nfc-preset-select');
    if (presetSelect) {
        presetSelect.addEventListener('change', () => {
            if (!presetSelect.value) return;
            const notes = $('nfc-notes');
            notes.value = notes.value.trim()
                ? notes.value.trim() + ' ' + presetSelect.value
                : presetSelect.value;
            presetSelect.value = '';
        });
    }
    document.querySelectorAll('[data-nfc-close]').forEach((b) =>
        b.addEventListener('click', () => $('nfc-modal').classList.add('hidden')));

    async function submit(continueToPayment) {
        const err = $('nfc-error');
        err.classList.add('hidden');

        if (!state.orderId && !state.customerId) {
            err.textContent = 'Select an order or a customer.';
            err.classList.remove('hidden');
            return;
        }
        if (!Number($('nfc-amount').value)) {
            err.textContent = 'Enter a charge amount.';
            err.classList.remove('hidden');
            return;
        }

        const payload = {
            order_id: state.orderId,
            customer_id: state.customerId,
            amount: Number($('nfc-amount').value),
            sales_tax_type: taxTreatment(),
            notes: $('nfc-notes').value.trim() || null,
            responsible_person: $('nfc-user').value,
            source_context: CONTEXT,
        };

        const res = await fetch(URLS.store, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || data.success === false) {
            err.textContent = data.message
                || Object.values(data.errors || {}).flat().join(' ')
                || 'The charge could not be created.';
            err.classList.remove('hidden');
            return;
        }

        $('nfc-modal').classList.add('hidden');
        if (window.NewFuelCharge.onCreated) {
            window.NewFuelCharge.onCreated(data.charge, continueToPayment);
        }
    }

    $('nfc-save').addEventListener('click', () => submit(false));
    $('nfc-save-pay').addEventListener('click', () => submit(true));

    window.NewFuelCharge = {
        onCreated: null,
        open(opts = {}) {
            // Launch modes (Billing Engine commonization):
            //   selection mode      — Dashboard / Fuel Workspace (default)
            //   locked-order mode   — Order Details (order + customer are
            //                         fixed context, no selectors)
            //   customer-locked     — CRM customer page
            state.orderLocked = !!opts.lockOrderContext;
            state.customerLocked = !!opts.lockCustomer || state.orderLocked;
            setOrder(null);

            if (state.orderLocked) {
                state.orderId = opts.orderId;
                show($('nfc-order-selected'), true);
                show($('nfc-order-search-wrap'), false);
                $('nfc-order-clear').classList.add('hidden');
                $('nfc-order-label').textContent = opts.orderLabel || 'Current order';
                setCustomer({ id: opts.customerId, name: opts.customerName || 'Customer' }, true);
            } else if (opts.customerId) {
                setCustomer({ id: opts.customerId, name: opts.customerName || 'Selected customer' }, !!opts.lockCustomer);
            } else {
                setCustomer(null, false);
            }
            $('nfc-amount').value = '';
            $('nfc-notes').value = '';
            // Taxable is always the default — No Tax requires intent.
            document.querySelector('input[name="nfc-tax"][value="add"]').checked = true;
            refreshSummary();
            $('nfc-error').classList.add('hidden');
            $('nfc-modal').classList.remove('hidden');
        },
    };
})();
</script>

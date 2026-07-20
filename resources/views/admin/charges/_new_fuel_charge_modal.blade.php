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

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" id="nfc-amount"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sales Tax</label>
                <select id="nfc-tax" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="free">Tax Free</option>
                    <option value="add">Add Sales Tax</option>
                    <option value="reverse">Reverse Sales Tax</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea id="nfc-notes" rows="2" maxlength="500"
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                      placeholder="Gallons, fuel level, context…"></textarea>
            @if ($fuelNotePresets->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach ($fuelNotePresets as $preset)
                        <button type="button" data-nfc-preset
                                class="px-2 py-1 rounded border border-gray-200 text-xs text-gray-600 hover:bg-gray-50">{{ $preset->label }}</button>
                    @endforeach
                </div>
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

        <div class="flex justify-end gap-2">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-nfc-close>Cancel</button>
            <button type="button" id="nfc-save"
                    class="px-4 py-2 text-sm rounded-md border border-orange-500 text-orange-600 hover:bg-orange-50">Save Charge — Pay Later</button>
            <button type="button" id="nfc-save-pay"
                    class="px-4 py-2 text-sm rounded-md bg-orange-500 text-white hover:bg-orange-600">Save Charge — Continue to Payment</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const CONTEXT = @json($nfcContext);
    const URLS = {
        store:     @json(route('admin.dashboard.fuel-charge.store')),
        orders:    @json(route('admin.dashboard.charge-modal.orders')),
        customers: @json(route('admin.dashboard.charge-modal.customers')),
    };

    const $ = (id) => document.getElementById(id);
    const state = { orderId: null, customerId: null, customerLocked: false };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function show(el, on) { el.classList.toggle('hidden', !on); el.classList.toggle('flex', on && el.id.endsWith('-selected')); }

    function setOrder(order) {
        state.orderId = order ? order.id : null;
        show($('nfc-order-selected'), !!order);
        show($('nfc-order-search-wrap'), !order);
        if (order) {
            $('nfc-order-label').textContent =
                `#${order.order_number} · ${order.customer_name} · ${order.order_date} · ${order.status}`;
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
                    <span class="font-medium">#${esc(o.order_number)}</span> · ${esc(o.customer_name)}
                    <span class="text-gray-400">· ${esc(o.order_date)} · ${esc(o.status)}</span>
                </div>`,
        (o) => setOrder(o));

    typeahead('nfc-customer-search', 'nfc-customer-results', URLS.customers,
        (c) => `<div data-pick='${esc(JSON.stringify(c))}' class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer">
                    <span class="font-medium">${esc(c.name)}</span>
                    <span class="text-gray-400">${c.company ? '· ' + esc(c.company) : ''} ${c.phone ? '· ' + esc(c.phone) : ''}</span>
                </div>`,
        (c) => setCustomer(c, false));

    $('nfc-order-clear').addEventListener('click', () => setOrder(null));
    $('nfc-customer-clear').addEventListener('click', () => setCustomer(null, false));
    document.querySelectorAll('[data-nfc-preset]').forEach((b) =>
        b.addEventListener('click', () => { $('nfc-notes').value = b.textContent.trim(); }));
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
            sales_tax_type: $('nfc-tax').value,
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
            setOrder(null);
            state.customerLocked = !!opts.lockCustomer;
            if (opts.customerId) {
                setCustomer({ id: opts.customerId, name: opts.customerName || 'Selected customer' }, !!opts.lockCustomer);
            } else {
                setCustomer(null, false);
            }
            $('nfc-amount').value = '';
            $('nfc-notes').value = '';
            $('nfc-tax').value = 'free';
            $('nfc-error').classList.add('hidden');
            $('nfc-modal').classList.remove('hidden');
        },
    };
})();
</script>

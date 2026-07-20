{{-- Dashboard V2 Phase 1A — shared charge-action modals + behavior.
     Included by the Fuel Charge Workspace (and, in Phase 1B, the Damage
     Workspace) with:
       $chargeType         'fuel' | 'damage'
       $users              active users for attribution selects
       $paymentSetting     Payment Settings (Accept.js keys)
       $resolutionPresets  ResolutionNotePreset list
       $fuelNotePresets    FuelNotePreset list

     Every action posts to the PRE-EXISTING canonical dashboard endpoints —
     no new business logic. The old 1,200-line dashboardApp was treated as
     a functional inventory only; this is a fresh, minimal implementation. --}}

@php $authUserId = auth()->id(); @endphp

{{-- ── Notes modal ─────────────────────────────────────────────────── --}}
<div id="ws-notes-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Add Note — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>
        <textarea id="ws-note-text" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Note…"></textarea>
        @if ($fuelNotePresets->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach ($fuelNotePresets as $preset)
                    <button type="button" data-note-preset
                            class="px-2 py-1 rounded border border-gray-200 text-xs text-gray-600 hover:bg-gray-50">{{ $preset->label }}</button>
                @endforeach
            </div>
        @endif
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Cancel</button>
            <button type="button" id="ws-note-save" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save Note</button>
        </div>
    </div>
</div>

{{-- ── Adjust amount modal ─────────────────────────────────────────── --}}
<div id="ws-adjust-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Adjust Amount — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>
        <p class="text-sm text-gray-600 mb-3">Current charge: <span id="ws-adjust-current" class="font-semibold text-gray-900"></span></p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment (+ / −)</label>
        <input type="number" step="0.01" id="ws-adjust-amount" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="e.g. 25 or -25">
        <p class="text-xs text-gray-500 mt-1">The adjustment is relative — new total: <span id="ws-adjust-preview" class="font-medium">—</span></p>
        <label class="block text-sm font-medium text-gray-700 mb-1 mt-3">Reason / note</label>
        <input type="text" id="ws-adjust-note" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Why is the amount changing?">
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Cancel</button>
            <button type="button" id="ws-adjust-save" class="px-4 py-2 text-sm rounded-md bg-purple-600 text-white hover:bg-purple-700">Save Adjustment</button>
        </div>
    </div>
</div>

{{-- ── Resolve modal ───────────────────────────────────────────────── --}}
<div id="ws-resolve-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Resolve — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Resolution note <span class="text-red-500">*</span></label>
        <textarea id="ws-resolve-note" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="How was this resolved?"></textarea>
        @if ($resolutionPresets->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach ($resolutionPresets as $preset)
                    <button type="button" data-resolve-preset
                            class="px-2 py-1 rounded border border-gray-200 text-xs text-gray-600 hover:bg-gray-50">{{ $preset->label }}</button>
                @endforeach
            </div>
        @endif
        <label class="block text-sm font-medium text-gray-700 mb-1 mt-3">Resolved by</label>
        <select id="ws-resolve-user" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected($u->id === $authUserId)>{{ $u->first_name }} {{ $u->last_name }}</option>
            @endforeach
        </select>
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Cancel</button>
            <button type="button" id="ws-resolve-save" class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">Mark Resolved</button>
        </div>
    </div>
</div>

{{-- ── Uncollectible modal ─────────────────────────────────────────── --}}
<div id="ws-uncollectible-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-2">Mark Uncollectible — <span data-modal-context></span></h3>
        <p class="text-sm text-gray-600">This removes the alert from the active queue and records it as uncollectible. This action is tracked in the alert lifecycle log.</p>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Cancel</button>
            <button type="button" id="ws-uncollectible-save" class="px-4 py-2 text-sm rounded-md bg-red-600 text-white hover:bg-red-700">Mark Uncollectible</button>
        </div>
    </div>
</div>

{{-- ── History modal ───────────────────────────────────────────────── --}}
<div id="ws-history-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Charge History — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>
        <div id="ws-history-body" class="text-sm text-gray-700">
            <p class="text-gray-400">Loading…</p>
        </div>
        <div class="flex justify-end mt-4">
            <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Close</button>
        </div>
    </div>
</div>

{{-- ── Payment modal — a REAL form POST to the canonical dashboard
       payment endpoint (redirect + flash on completion, exactly like the
       old dashboard modal and the CRM quick-payment form). ───────────── --}}
<div id="ws-payment-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Collect Payment — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>

        <form id="ws-payment-form" method="POST" action="{{ route('admin.dashboard.paymentstore') }}">
            @csrf
            <input type="hidden" name="customer_id" id="wsp-customer-id">
            <input type="hidden" name="order_id" id="wsp-order-id">
            <input type="hidden" name="order_product_id" id="wsp-op-id">
            <input type="hidden" name="type" value="{{ $chargeType }}">
            <input type="hidden" name="source" value="order">
            <input type="hidden" name="idempotency_token" id="wsp-idempotency">
            <input type="hidden" name="opaqueDataValue" id="wsp-opaque-value">
            <input type="hidden" name="opaqueDataDescriptor" id="wsp-opaque-descriptor">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" id="wsp-amount" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                <select name="payment_type" id="wsp-payment-type" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @foreach (\App\Enums\Customers\PaymentMethod::options() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4 hidden" id="wsp-cheque-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Check Number</label>
                <input type="text" name="cheque_number" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>

            <div class="mb-4 hidden" id="wsp-card-options-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Card Option <span class="text-red-500">*</span></label>
                <select name="card_option" id="wsp-card-option" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="CardOnFile">Card on File</option>
                    <option value="NewCard">New Card</option>
                </select>
            </div>

            <div class="mb-4 hidden" id="wsp-saved-cards-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Saved Card <span class="text-red-500">*</span></label>
                <select name="existing_card_id" id="wsp-existing-card" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></select>
            </div>

            <div class="hidden" id="wsp-new-card-wrap">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <input type="text" name="firstName" placeholder="First name" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <input type="text" name="lastName" placeholder="Last name" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <input type="text" id="wsp-card-number" placeholder="Card number" autocomplete="off"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-3">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <input type="text" id="wsp-card-expiry" placeholder="MM/YY" autocomplete="off" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <input type="text" id="wsp-card-cvc" placeholder="CVC" autocomplete="off" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Received By <span class="text-red-500">*</span></label>
                <select name="responsible_person" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected($u->id === $authUserId)>{{ $u->first_name }} {{ $u->last_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4" id="wsp-notes-wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-red-500 hidden" id="wsp-notes-required">*</span></label>
                <input type="text" name="notes" id="wsp-notes" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                       placeholder="Optional — required for Other">
            </div>

            <div id="ws-payment-error" class="hidden mb-3 text-sm text-red-600"></div>

            <div class="flex justify-end gap-2">
                <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Cancel</button>
                <button type="submit" id="wsp-submit" class="px-4 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Collect Payment</button>
            </div>
        </form>
    </div>
</div>

@if (app()->environment('production'))
    <script src="https://js.authorize.net/v1/Accept.js"></script>
@else
    <script src="https://jstest.authorize.net/v1/Accept.js"></script>
@endif

<script>
(function () {
    'use strict';

    const TYPE = @json($chargeType);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const AUTH_USER_ID = @json($authUserId);
    const ACCEPT_AUTH = {
        clientKey: @json(safe_decrypt($paymentSetting['payment_api_public_key'] ?? null)),
        apiLoginID: @json(safe_decrypt($paymentSetting['payment_api_key'] ?? null)),
    };

    // Canonical endpoints — path templates resolved per row at open time.
    const URLS = {
        notes:         @json(route('admin.dashboard.notes.store', ':oid')),
        adjust:        @json(route('admin.dashboard.amount.update', ':opuid')),
        resolve:       @json(route('admin.dashboard.extra-charges.resolved', ':opid')),
        uncollectible: @json(route('admin.dashboard.extra-charges.uncollectible', ':opid')),
        history:       @json(route('admin.dashboard.extra-charges.show', ':orderid')),
    };

    let activeRow = null;

    const $ = (id) => document.getElementById(id);
    const money = (n) => '$' + Number(n).toFixed(2);

    function openModal(id) {
        const modal = $(id);
        modal.classList.remove('hidden');
        modal.querySelectorAll('[data-modal-context]').forEach((el) => {
            el.textContent = activeRow.dataset.customerName
                + (activeRow.dataset.orderNumber ? ' · ' + activeRow.dataset.orderNumber : '');
        });
    }

    function closeModals() {
        document.querySelectorAll('[id^="ws-"][id$="-modal"]').forEach((m) => m.classList.add('hidden'));
    }
    document.querySelectorAll('[data-modal-close]').forEach((b) => b.addEventListener('click', closeModals));

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'The action could not be completed.');
        }
        return data;
    }

    // Refresh queue + summary in place, preserving current filters/page.
    async function refreshWorkspace() {
        const params = new URLSearchParams(window.wsCurrentFilters ? window.wsCurrentFilters() : {});
        params.set('fragment', '1');
        const res = await fetch(`${window.location.pathname}?${params}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        document.querySelector('[data-queue-wrap]').innerHTML = data.queue_html;
        document.querySelector('[data-summary-wrap]').innerHTML = data.summary_html;
        bindRowActions();
    }
    window.wsRefreshWorkspace = refreshWorkspace;

    function bindRowActions() {
        document.querySelectorAll('[data-alert-row] [data-action]').forEach((btn) => {
            btn.addEventListener('click', () => {
                activeRow = btn.closest('[data-alert-row]');
                const action = btn.dataset.action;

                if (action === 'notes') {
                    $('ws-note-text').value = '';
                    openModal('ws-notes-modal');
                } else if (action === 'adjust') {
                    $('ws-adjust-current').textContent = money(activeRow.dataset.amount);
                    $('ws-adjust-amount').value = '';
                    $('ws-adjust-note').value = '';
                    $('ws-adjust-preview').textContent = money(activeRow.dataset.amount);
                    openModal('ws-adjust-modal');
                } else if (action === 'resolve') {
                    $('ws-resolve-note').value = '';
                    openModal('ws-resolve-modal');
                } else if (action === 'uncollectible') {
                    openModal('ws-uncollectible-modal');
                } else if (action === 'history') {
                    openModal('ws-history-modal');
                    loadHistory(activeRow.dataset.orderDbId);
                } else if (action === 'payment') {
                    prepPaymentModal();
                    openModal('ws-payment-modal');
                }
            });
        });
    }
    bindRowActions();
    window.wsBindRowActions = bindRowActions;

    // ── Notes ──────────────────────────────────────────────────────────
    document.querySelectorAll('[data-note-preset]').forEach((b) =>
        b.addEventListener('click', () => { $('ws-note-text').value = b.textContent.trim(); }));

    $('ws-note-save').addEventListener('click', async () => {
        const note = $('ws-note-text').value.trim();
        if (!note) return;
        try {
            await postJson(URLS.notes.replace(':oid', activeRow.dataset.orderUid), { note, user_id: AUTH_USER_ID });
            closeModals();
            refreshWorkspace();
        } catch (e) { alert(e.message); }
    });

    // ── Adjust ─────────────────────────────────────────────────────────
    $('ws-adjust-amount').addEventListener('input', () => {
        const next = Number(activeRow.dataset.amount) + (Number($('ws-adjust-amount').value) || 0);
        $('ws-adjust-preview').textContent = money(Math.max(0, next));
    });

    $('ws-adjust-save').addEventListener('click', async () => {
        const change = Number($('ws-adjust-amount').value);
        if (!change) return;
        try {
            await postJson(URLS.adjust.replace(':opuid', activeRow.dataset.opUid), {
                amount: change, type: TYPE, note: $('ws-adjust-note').value.trim(),
            });
            closeModals();
            refreshWorkspace();
        } catch (e) { alert(e.message); }
    });

    // ── Resolve ────────────────────────────────────────────────────────
    document.querySelectorAll('[data-resolve-preset]').forEach((b) =>
        b.addEventListener('click', () => { $('ws-resolve-note').value = b.textContent.trim(); }));

    $('ws-resolve-save').addEventListener('click', async () => {
        const note = $('ws-resolve-note').value.trim();
        if (!note) { alert('A resolution note is required.'); return; }
        try {
            await postJson(URLS.resolve.replace(':opid', activeRow.dataset.opId), {
                type: TYPE, resolution_note: note, resolved_by: $('ws-resolve-user').value,
            });
            closeModals();
            refreshWorkspace();
        } catch (e) { alert(e.message); }
    });

    // ── Uncollectible ──────────────────────────────────────────────────
    $('ws-uncollectible-save').addEventListener('click', async () => {
        try {
            await postJson(URLS.uncollectible.replace(':opid', activeRow.dataset.opId), { type: TYPE });
            closeModals();
            refreshWorkspace();
        } catch (e) { alert(e.message); }
    });

    // ── History ────────────────────────────────────────────────────────
    async function loadHistory(orderDbId) {
        const body = $('ws-history-body');
        body.innerHTML = '<p class="text-gray-400">Loading…</p>';
        try {
            const res = await fetch(URLS.history.replace(':orderid', orderDbId), { headers: { 'Accept': 'application/json' } });
            const d = await res.json();
            const rows = (d.checklist?.rows || []).map((r) =>
                `<tr><td class="py-1 pr-3">${r.item}</td><td class="py-1 pr-3">${r.delivered}</td><td class="py-1 pr-3">${r.returned}</td><td class="py-1 text-right">${money(r.amount)}</td></tr>`
            ).join('');
            body.innerHTML = `
                ${rows ? `<table class="w-full text-sm mb-4"><thead><tr class="text-left text-gray-500 border-b"><th class="py-1 pr-3">Item</th><th class="py-1 pr-3">Delivered</th><th class="py-1 pr-3">Returned</th><th class="py-1 text-right">Amount</th></tr></thead><tbody>${rows}</tbody></table>` : ''}
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span>Checklist total</span><span>${money(d.checklist?.total || 0)}</span></div>
                    <div class="flex justify-between"><span>Damage (base / adj / final)</span><span>${money(d.damage?.base || 0)} / ${money(d.damage?.adjustment || 0)} / ${money(d.damage?.final || 0)}</span></div>
                    <div class="flex justify-between"><span>Hour tracking</span><span>${money(d.hour_tracking?.total || 0)}</span></div>
                    <div class="flex justify-between font-semibold border-t pt-1"><span>Grand total</span><span>${money(d.grand_total || 0)}</span></div>
                </div>`;
        } catch (e) {
            body.innerHTML = '<p class="text-red-600 text-sm">Could not load charge history.</p>';
        }
    }

    // ── Payment ────────────────────────────────────────────────────────
    function prepPaymentModal() {
        $('wsp-customer-id').value = activeRow.dataset.customerId;
        $('wsp-order-id').value = activeRow.dataset.orderDbId || '';
        $('wsp-op-id').value = activeRow.dataset.opId || '';
        $('wsp-amount').value = activeRow.dataset.amount;
        $('wsp-idempotency').value = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random());
        $('wsp-opaque-value').value = '';
        $('wsp-opaque-descriptor').value = '';
        $('ws-payment-error').classList.add('hidden');

        const cards = JSON.parse(activeRow.dataset.cards || '[]');
        const sel = $('wsp-existing-card');
        sel.innerHTML = cards.map((c) => `<option value="${c.id}">${c.label}</option>`).join('')
            || '<option value="">No saved cards</option>';

        syncPaymentFields();
    }

    function syncPaymentFields() {
        const method = $('wsp-payment-type').value;
        const isCard = method === 'CreditCard';
        $('wsp-cheque-wrap').classList.toggle('hidden', method !== 'Cheque');
        $('wsp-card-options-wrap').classList.toggle('hidden', !isCard);
        $('wsp-notes-required').classList.toggle('hidden', method !== 'Other');
        $('wsp-notes').required = method === 'Other';

        const option = $('wsp-card-option').value;
        $('wsp-saved-cards-wrap').classList.toggle('hidden', !(isCard && option === 'CardOnFile'));
        $('wsp-new-card-wrap').classList.toggle('hidden', !(isCard && option === 'NewCard'));
    }
    $('wsp-payment-type').addEventListener('change', syncPaymentFields);
    $('wsp-card-option').addEventListener('change', syncPaymentFields);

    $('ws-payment-form').addEventListener('submit', function (e) {
        const method = $('wsp-payment-type').value;
        const option = $('wsp-card-option').value;

        if (method !== 'CreditCard' || option !== 'NewCard') {
            return; // plain form POST — canonical redirect + flash flow
        }

        // New card: tokenize via Accept.js first, then submit with opaque data.
        e.preventDefault();
        const err = $('ws-payment-error');
        err.classList.add('hidden');

        const [month, year] = ($('wsp-card-expiry').value || '').split('/');
        Accept.dispatchData({
            authData: ACCEPT_AUTH,
            cardData: {
                cardNumber: ($('wsp-card-number').value || '').replace(/\s+/g, ''),
                month: (month || '').trim(),
                year: (year || '').trim(),
                cardCode: ($('wsp-card-cvc').value || '').trim(),
            },
        }, (response) => {
            if (response.messages.resultCode === 'Error') {
                err.textContent = response.messages.message.map((m) => m.text).join(' ');
                err.classList.remove('hidden');
                return;
            }
            $('wsp-opaque-value').value = response.opaqueData.dataValue;
            $('wsp-opaque-descriptor').value = response.opaqueData.dataDescriptor;
            // PAN never leaves the browser — only the token is posted.
            $('wsp-card-number').value = '';
            $('wsp-card-cvc').value = '';
            $('ws-payment-form').submit();
        });
    });
})();
</script>

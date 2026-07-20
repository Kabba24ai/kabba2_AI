{{-- Billing Charge Operations Commonization — THE shared charge-action
     modals + behavior for every surface that manages charges (Fuel
     Workspace, Order Details Billing Engine, future Damage Workspace).
     Included with:
       $chargeType         'fuel' | 'damage'  (preset flavor + op-mode type)
       $users              active users for attribution selects
       $paymentSetting     Payment Settings (Accept.js keys)
       $resolutionPresets  ResolutionNotePreset list
       $fuelNotePresets    FuelNotePreset list

     Rows opt in via data-charge-row + data-action-mode:
       'op'     — legacy OrderProduct-keyed rows: actions post to the
                  pre-existing canonical dashboard endpoints (unchanged).
       'charge' — BillingCharge-keyed rows: actions post to the canonical
                  billing-charges.* endpoints (unchanged).
     The MODALS, wording, icons, and flow are identical either way — only
     the endpoint family differs, chosen per row, never per page.

     No business logic lives here. Payment is the shared Billing Engine
     component (admin/billing/_payment_modal); refunds post to the
     PRE-EXISTING linked-refund endpoint (RefundStoreController::
     storeLinkedRefund — eligibility, remaining-refundable, split, locking
     and idempotency are all enforced server-side there).

     After a successful action the bundle calls ChargeActions.onChanged()
     — full page reload by default; a page may plug in its own refresher
     (the workspace swaps queue fragments in place). --}}

@include('admin.billing._payment_modal')

@php $authUserId = auth()->id(); @endphp

{{-- ── Notes modal ─────────────────────────────────────────────────── --}}
<div id="ws-notes-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Add Note — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>
        <textarea id="ws-note-text" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Note…"></textarea>
        <x-admin.billing.note-preset-select id="ws-note-preset-select" type="fuel"
            :presets="$fuelNotePresets" target-id="ws-note-text" />
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
        <x-admin.billing.note-preset-select id="ws-resolve-preset-select" type="resolution"
            :presets="$resolutionPresets" target-id="ws-resolve-note" />
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
        <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Note <span class="text-gray-400 font-normal">— optional</span></label>
        <textarea id="ws-uncollectible-note" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Why is this uncollectible?"></textarea>
        <x-admin.billing.note-preset-select id="ws-uncollectible-preset-select" type="resolution"
            :presets="$resolutionPresets" target-id="ws-uncollectible-note" />
        <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Marked by</label>
        <select id="ws-uncollectible-user" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected($u->id === $authUserId)>{{ $u->first_name }} {{ $u->last_name }}</option>
            @endforeach
        </select>
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

{{-- ── Refund modal ────────────────────────────────────────────────────
     A REAL form POST to the pre-existing linked-refund endpoint
     (RefundStoreController::storeLinkedRefund). Only PAID fuel/damage
     charges with a remaining refundable balance render the refund action;
     the server independently re-derives eligibility, remaining balance,
     and the base/tax split — nothing here is trusted. Redirect + flash on
     completion, same as the CRM Process Refund flow. --}}
<div id="ws-refund-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Refund — <span data-modal-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-modal-close>&times;</button>
        </div>
        <form id="ws-refund-form" method="POST" action="{{ route('admin.crm.customers.customer-account.refundstore') }}">
            @csrf
            <input type="hidden" name="customer_id" id="ws-refund-customer-id">
            <input type="hidden" name="billing_charge_unique_id" id="ws-refund-bc-id">
            <input type="hidden" name="idempotency_token" id="ws-refund-idempotency">

            <p class="text-sm text-gray-600 mb-3">
                Remaining refundable: <span id="ws-refund-remaining" class="font-semibold text-gray-900"></span>
            </p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" id="ws-refund-amount" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">Tax-inclusive — the base/tax split is derived from the original charge.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Refund Reason <span class="text-red-500">*</span></label>
                <select name="reason" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">Select refund reason</option>
                    <option value="Billing Overcharge">Billing Overcharge</option>
                    <option value="Damage Waiver Protection">Damage Waiver Protection</option>
                    <option value="Customer Cancellation">Customer Cancellation</option>
                    <option value="Damaged Item">Damaged Item</option>
                    <option value="Duplicate Charge">Duplicate Charge</option>
                    <option value="Wrong Item Shipped">Wrong Item Shipped</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Person Responsible <span class="text-red-500">*</span></label>
                <select name="responsible_person" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected($u->id === $authUserId)>{{ $u->first_name }} {{ $u->last_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-modal-close>Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm rounded-md bg-amber-500 text-white hover:bg-amber-600">Process Refund</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    const TYPE = @json($chargeType);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const AUTH_USER_ID = @json($authUserId);

    // Canonical endpoints — path templates resolved per row at open time.
    // Two families, chosen by each row's data-action-mode (never by page):
    //   op:     legacy OrderProduct-keyed dashboard endpoints
    //   charge: BillingCharge-keyed billing-engine endpoints
    const URLS = {
        notes:               @json(route('admin.dashboard.notes.store', ':oid')),
        adjust:              @json(route('admin.dashboard.amount.update', ':opuid')),
        resolve:             @json(route('admin.dashboard.extra-charges.resolved', ':opid')),
        uncollectible:       @json(route('admin.dashboard.extra-charges.uncollectible', ':opid')),
        history:             @json(route('admin.dashboard.extra-charges.show', ':orderid')),
        noteCharge:          @json(route('admin.order-management.orders.billing-charges.note', ':bcid')),
        adjustCharge:        @json(route('admin.order-management.orders.billing-charges.adjust', ':bcid')),
        resolveCharge:       @json(route('admin.order-management.orders.billing-charges.resolve', ':bcid')),
        uncollectibleCharge: @json(route('admin.order-management.orders.billing-charges.uncollectible', ':bcid')),
    };

    let activeRow = null;

    const $ = (id) => document.getElementById(id);
    const money = (n) => '$' + Number(n).toFixed(2);
    const isChargeMode = () => activeRow?.dataset.actionMode === 'charge';
    const rowType = () => activeRow?.dataset.type || TYPE;

    function freshToken() {
        return (window.crypto && window.crypto.randomUUID)
            ? window.crypto.randomUUID()
            : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    }

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

    function bindRowActions() {
        document.querySelectorAll('[data-charge-row] [data-action]').forEach((btn) => {
            btn.addEventListener('click', () => {
                activeRow = btn.closest('[data-charge-row]');
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
                    $('ws-uncollectible-note').value = '';
                    openModal('ws-uncollectible-modal');
                } else if (action === 'history') {
                    openModal('ws-history-modal');
                    loadHistory(activeRow.dataset.orderDbId);
                } else if (action === 'payment') {
                    const label = activeRow.dataset.customerName
                        + (activeRow.dataset.orderNumber ? ' · ' + activeRow.dataset.orderNumber : '');
                    if (isChargeMode()) {
                        window.BillingPayment.openForBillingRow(activeRow.dataset, label);
                    } else {
                        window.BillingPayment.openForAlertRow(activeRow.dataset, label);
                    }
                } else if (action === 'refund') {
                    const remaining = Number(activeRow.dataset.refundRemaining || 0);
                    $('ws-refund-customer-id').value = activeRow.dataset.customerId || '';
                    $('ws-refund-bc-id').value = activeRow.dataset.bcId || '';
                    $('ws-refund-idempotency').value = freshToken();
                    $('ws-refund-remaining').textContent = money(remaining);
                    const amt = $('ws-refund-amount');
                    amt.value = remaining.toFixed(2);
                    amt.max = remaining.toFixed(2);
                    openModal('ws-refund-modal');
                } else {
                    // Page-specific actions (e.g. view-damage, extension
                    // delete) — the host page plugs in a handler.
                    window.ChargeActions.onAction?.(action, activeRow);
                }
            });
        });
    }
    bindRowActions();

    // Pluggable post-action refresh: full reload by default (Order Details),
    // replaced by the workspace with its in-place fragment refresh.
    window.ChargeActions = {
        bind: bindRowActions,
        onChanged: () => window.location.reload(),
    };
    // Back-compat alias for pages that still call the old name.
    window.wsBindRowActions = bindRowActions;

    function changed() {
        closeModals();
        window.ChargeActions.onChanged();
    }

    // ── Notes ──────────────────────────────────────────────────────────
    // Preset insertion + management is the shared BillingNotePresets
    // component (x-admin.billing.note-preset-select) — nothing lives here.

    $('ws-note-save').addEventListener('click', async () => {
        const note = $('ws-note-text').value.trim();
        if (!note) return;
        try {
            if (isChargeMode()) {
                await postJson(URLS.noteCharge.replace(':bcid', activeRow.dataset.bcId), { note });
            } else {
                await postJson(URLS.notes.replace(':oid', activeRow.dataset.orderUid), { note, user_id: AUTH_USER_ID });
            }
            changed();
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
        const note = $('ws-adjust-note').value.trim();
        try {
            if (isChargeMode()) {
                await postJson(URLS.adjustCharge.replace(':bcid', activeRow.dataset.bcId), { amount: change, note });
            } else {
                await postJson(URLS.adjust.replace(':opuid', activeRow.dataset.opUid), { amount: change, type: rowType(), note });
            }
            changed();
        } catch (e) { alert(e.message); }
    });

    // ── Resolve ────────────────────────────────────────────────────────
    $('ws-resolve-save').addEventListener('click', async () => {
        const note = $('ws-resolve-note').value.trim();
        if (!note) { alert('A resolution note is required.'); return; }
        const by = $('ws-resolve-user').value;
        try {
            if (isChargeMode()) {
                await postJson(URLS.resolveCharge.replace(':bcid', activeRow.dataset.bcId), { resolution_note: note, resolved_by: by });
            } else {
                await postJson(URLS.resolve.replace(':opid', activeRow.dataset.opId), { type: rowType(), resolution_note: note, resolved_by: by });
            }
            changed();
        } catch (e) { alert(e.message); }
    });

    // ── Uncollectible ──────────────────────────────────────────────────
    $('ws-uncollectible-save').addEventListener('click', async () => {
        const by = $('ws-uncollectible-user').value;
        const note = $('ws-uncollectible-note').value.trim();
        try {
            if (isChargeMode()) {
                await postJson(URLS.uncollectibleCharge.replace(':bcid', activeRow.dataset.bcId), { resolved_by: by });
            } else {
                await postJson(URLS.uncollectible.replace(':opid', activeRow.dataset.opId), { type: rowType() });
            }
        } catch (e) { alert(e.message); return; }

        // Optional context note — recorded through the EXISTING canonical
        // note pathways (order note for OP rows, charge note for
        // BillingCharge rows); the uncollectible transition itself is
        // untouched. A note failure never undoes the completed transition.
        if (note) {
            try {
                if (isChargeMode()) {
                    await postJson(URLS.noteCharge.replace(':bcid', activeRow.dataset.bcId), { note: 'Uncollectible — ' + note });
                } else {
                    await postJson(URLS.notes.replace(':oid', activeRow.dataset.orderUid), { note: 'Uncollectible — ' + note, user_id: AUTH_USER_ID });
                }
            } catch (e) {
                alert('Marked uncollectible, but the note could not be saved: ' + e.message);
            }
        }
        changed();
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
})();
</script>

{{-- Billing Engine — the ONE post-charge payment modal (Billing Engine
     Commonization). Every surface that collects payment against a billing
     charge includes THIS partial and opens it through window.BillingPayment;
     no module ships its own payment UI. Posts as a real form to the
     canonical PaymentStoreController (admin.dashboard.paymentstore), which
     already dispatches every source shape server-side:
       source=order + order_product_id            → OP-linked charge payment
       source=crm   + customer_account_id (+bc id) → manual/CRM charge payment
       source=crm   + billing_charge_unique_id     → extension charge payment

     Include vars: $users, $paymentSetting. Wrapped in @once — pages that
     also include _action_modals never end up with duplicate DOM.

     JS API:
       BillingPayment.openForAlertRow(dataset, label)  — OrderProduct-keyed
                                                         queue rows (source=order)
       BillingPayment.openForCharge(charge, label)     — just-created charges
                                                         (the shared creation-
                                                         response contract)
       BillingPayment.openForBillingRow(dataset, label) — existing BillingCharge
                                                         rows (Billing Engine
                                                         table, CRM-origin queue
                                                         rows); amount locked to
                                                         the charge total the
                                                         server enforces
       BillingPayment.openForExtension(charge, opts)   — step 2 of Add Extension
                                                         Charge (pay now / save
                                                         as Pay Later)
       BillingPayment.showError(message)               — surface a failed-
                                                         attempt reason inside
                                                         the open modal --}}
@once
@php $bpAuthUserId = auth()->id(); @endphp

<div id="ws-payment-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Collect Payment — <span data-bp-context></span></h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" data-bp-close>&times;</button>
        </div>

        <form id="ws-payment-form" method="POST" action="{{ route('admin.dashboard.paymentstore') }}">
            @csrf
            <input type="hidden" name="customer_id" id="wsp-customer-id">
            <input type="hidden" name="order_id" id="wsp-order-id">
            <input type="hidden" name="order_product_id" id="wsp-op-id">
            <input type="hidden" name="type" id="wsp-type" value="fuel">
            <input type="hidden" name="source" id="wsp-source" value="order">
            <input type="hidden" name="customer_account_id" id="wsp-ca-id">
            <input type="hidden" name="billing_charge_unique_id" id="wsp-bc-id">
            <input type="hidden" name="idempotency_token" id="wsp-idempotency">
            <input type="hidden" name="opaqueDataValue" id="wsp-opaque-value">
            <input type="hidden" name="opaqueDataDescriptor" id="wsp-opaque-descriptor">

            {{-- Flow context (e.g. step 2 of Add Extension Charge) --}}
            <div id="wsp-context" class="hidden mb-4 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2">
                <p class="text-xs font-semibold text-orange-700 uppercase tracking-wide" id="wsp-context-title"></p>
                <p id="wsp-context-text" class="text-sm text-orange-800 mt-0.5"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" id="wsp-amount" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                <p id="wsp-amount-note" class="hidden text-xs text-gray-500 mt-1">Charge total including tax — collected in full.</p>
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
                <select name="responsible_person" id="wsp-person" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected($u->id === $bpAuthUserId)>{{ $u->first_name }} {{ $u->last_name }}</option>
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
                <button type="button" id="wsp-cancel" class="px-4 py-2 text-sm rounded-md border border-gray-300" data-bp-close>Cancel</button>
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

    const ACCEPT_AUTH = {
        clientKey: @json(safe_decrypt($paymentSetting['payment_api_public_key'] ?? null)),
        apiLoginID: @json(safe_decrypt($paymentSetting['payment_api_key'] ?? null)),
    };

    const $ = (id) => document.getElementById(id);

    function freshToken() {
        return crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random();
    }

    // Extension pay-now flow state: closing the modal without paying must
    // reload so the just-created (still unpaid) extension row appears.
    let reloadOnClose = false;

    function resetCommon() {
        $('wsp-idempotency').value = freshToken();
        $('wsp-opaque-value').value = '';
        $('wsp-opaque-descriptor').value = '';
        $('ws-payment-error').classList.add('hidden');
        $('wsp-context').classList.add('hidden');
        $('wsp-submit').textContent = 'Collect Payment';
        $('wsp-cancel').textContent = 'Cancel';
        reloadOnClose = false;
    }

    function setCards(cards) {
        $('wsp-existing-card').innerHTML = (cards || []).map(
            (c) => `<option value="${c.id}">${c.label}</option>`
        ).join('') || '<option value="">No saved cards</option>';
    }

    function openModal(label) {
        const modal = $('ws-payment-modal');
        modal.classList.remove('hidden');
        modal.querySelectorAll('[data-bp-context]').forEach((el) => { el.textContent = label || ''; });
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

    document.querySelectorAll('[data-bp-close]').forEach((b) =>
        b.addEventListener('click', () => {
            $('ws-payment-modal').classList.add('hidden');
            if (reloadOnClose) {
                reloadOnClose = false;
                window.location.reload();
            }
        }));

    window.BillingPayment = {
        /**
         * Workspace queue row (OrderProduct-linked charge): amount editable,
         * source=order, type from the row's own alert type.
         */
        openForAlertRow(ds, label) {
            resetCommon();
            $('wsp-source').value = 'order';
            $('wsp-type').value = ds.type || 'fuel';
            $('wsp-ca-id').value = '';
            $('wsp-bc-id').value = '';
            $('wsp-customer-id').value = ds.customerId;
            $('wsp-order-id').value = ds.orderDbId || '';
            $('wsp-op-id').value = ds.opId || '';
            $('wsp-amount').value = ds.amount;
            $('wsp-amount').readOnly = false;
            $('wsp-amount-note').classList.add('hidden');
            setCards(JSON.parse(ds.cards || '[]'));
            syncPaymentFields();
            openModal(label);
        },

        /**
         * Just-created charge (the shared creation-response contract from
         * ChargeService::createManualCharge callers): source=crm with the
         * BillingCharge reference, so PaymentStoreController enforces the
         * exact tax-inclusive total and syncs BillingEngine::markPaid. The
         * amount is locked — these charges are settled in full by design.
         */
        openForCharge(charge, label) {
            resetCommon();
            $('wsp-source').value = 'crm';
            $('wsp-type').value = charge.type || 'fuel';
            $('wsp-ca-id').value = charge.customer_account_unique_id || '';
            $('wsp-bc-id').value = charge.billing_charge_unique_id || '';
            $('wsp-customer-id').value = charge.customer_id;
            $('wsp-order-id').value = charge.order_id || '';
            $('wsp-op-id').value = '';
            $('wsp-amount').value = Number(charge.amount_total ?? charge.amount).toFixed(2);
            $('wsp-amount').readOnly = true;
            $('wsp-amount-note').classList.remove('hidden');
            setCards(charge.customer_cards || []);
            syncPaymentFields();
            openModal(label);
        },

        /**
         * Existing BillingCharge row (Billing Engine table on Order
         * Details, CRM-origin workspace rows): source=crm with the CA and
         * BillingCharge references, so PaymentStoreController runs its
         * charge-linked path — exact tax-inclusive total enforced
         * server-side, BillingEngine::markPaid synced. The amount is
         * locked to the total the server will accept.
         */
        openForBillingRow(ds, label) {
            resetCommon();
            $('wsp-source').value = 'crm';
            $('wsp-type').value = ds.type || 'fuel';
            $('wsp-ca-id').value = ds.caId || '';
            $('wsp-bc-id').value = ds.bcId || '';
            $('wsp-customer-id').value = ds.customerId || '';
            $('wsp-order-id').value = '';
            $('wsp-op-id').value = '';
            $('wsp-amount').value = Number(ds.amountTotal || ds.amount || 0).toFixed(2);
            $('wsp-amount').readOnly = true;
            $('wsp-amount-note').classList.remove('hidden');
            setCards(JSON.parse(ds.cards || '[]'));
            syncPaymentFields();
            openModal(label);
        },

        /**
         * Step 2 of Add Extension Charge: pay the just-created extension
         * now, or close as Pay Later (closing reloads so the unpaid row
         * appears in the Billing Engine table). Same form, same
         * controller, same gateway path as every other charge payment.
         */
        openForExtension(charge, opts = {}) {
            resetCommon();
            reloadOnClose = true;
            $('wsp-source').value = 'crm';
            $('wsp-type').value = 'extension';
            $('wsp-ca-id').value = '';
            $('wsp-bc-id').value = charge.unique_id || '';
            $('wsp-customer-id').value = charge.customer_id || '';
            $('wsp-order-id').value = '';
            $('wsp-op-id').value = '';
            $('wsp-amount').value = Number(charge.total || 0).toFixed(2);
            $('wsp-amount').readOnly = true;
            $('wsp-amount-note').classList.remove('hidden');
            if (opts.personId) $('wsp-person').value = opts.personId;
            $('wsp-context-title').textContent = 'Extension Created';
            $('wsp-context-text').textContent =
                'Extension ' + (charge.order_number || '') + ' — $' + Number(charge.total || 0).toFixed(2)
                + '. Record the payment now, or save as Pay Later.';
            $('wsp-context').classList.remove('hidden');
            $('wsp-submit').textContent = 'Create Extension & Record Payment';
            $('wsp-cancel').textContent = 'Save as Pay Later';
            setCards(opts.cards || []);
            syncPaymentFields();
            openModal(opts.label || ('Extension ' + (charge.order_number || '')));
        },

        /** Show a failure reason (e.g. a declined gateway attempt) inside the modal. */
        showError(message) {
            const err = $('ws-payment-error');
            err.textContent = message || 'The payment could not be completed.';
            err.classList.remove('hidden');
        },
    };

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
@endonce

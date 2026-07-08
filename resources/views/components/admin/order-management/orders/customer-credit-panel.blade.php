{{--
    Phase 3.2 — Order Entry Integration.
    Internal only. Manual application only — no automatic credit
    application exists here or anywhere. Every figure below is sourced
    from CustomerCreditService (via EditController); nothing here
    recomputes a credit balance independently.
--}}
@props(['order', 'customerCreditSummary', 'orderAppliedCredit', 'employees'])

@php
    $remainingOrderBalance = max(0, round($order->balance_due - $orderAppliedCredit, 2));
    $finalBalance = max(0, round($order->balance_due - $orderAppliedCredit, 2));
    $hasCredit = $customerCreditSummary['balance'] > 0;
@endphp

@can('customer_credit.view')
<div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col relative"
    id="customerCreditPanel"
    data-available-balance="{{ $customerCreditSummary['balance'] }}"
    data-applied-to-order="{{ $orderAppliedCredit }}"
    data-order-balance-due="{{ $order->balance_due }}">

    <h2 class="text-black font-semibold text-lg mb-3">Customer Credit</h2>

    @if (!$hasCredit && $orderAppliedCredit <= 0)
        <p class="text-sm text-gray-500">No Store Credit Available</p>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Available Store Credit</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customerCreditSummary['balance']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Lifetime Credit Granted</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customerCreditSummary['lifetime_granted']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Lifetime Credit Redeemed</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customerCreditSummary['lifetime_redeemed']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Current Credit Balance</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($customerCreditSummary['balance']) }}</div>
            </div>
        </div>
    @endif

    {{-- Payment Summary --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 space-y-1 text-sm">
        <div class="flex justify-between"><span>Order Total:</span><span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</span></div>
        <div class="flex justify-between"><span>Store Credit Applied:</span><span class="font-medium text-green-700" id="ccAppliedDisplay">-{{ \App\Helpers\CustomHelper::formatCurrency($orderAppliedCredit) }}</span></div>
        <div class="flex justify-between"><span>Remaining Balance Due:</span><span class="font-medium" id="ccRemainingDueDisplay">{{ \App\Helpers\CustomHelper::formatCurrency($remainingOrderBalance) }}</span></div>
        <div class="flex justify-between"><span>Customer Payment:</span><span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($order->total_paid) }}</span></div>
        <div class="flex justify-between border-t border-blue-200 pt-1 font-bold text-gray-900"><span>Final Balance:</span><span id="ccFinalBalanceDisplay">{{ \App\Helpers\CustomHelper::formatCurrency($finalBalance) }}</span></div>
    </div>

    <div class="flex gap-2 flex-wrap">
        @can('customer_credit.redeem')
            <button type="button" id="openApplyCreditModal"
                class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 shadow-sm"
                @if(!$hasCredit || $remainingOrderBalance <= 0) disabled @endif>
                Apply Store Credit
            </button>
        @endcan
        @can('customer_credit.grant')
            <button type="button" id="openRemoveCreditModal"
                class="px-4 py-2 rounded-md border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-100"
                @if($orderAppliedCredit <= 0) disabled @endif>
                Remove Applied Credit
            </button>
        @endcan
    </div>
</div>
@endcan

@can('customer_credit.redeem')
<div id="applyCreditModalWrapper" style="display:none" class="fixed inset-0 z-[99999] overflow-y-auto bg-gray-500/75 flex justify-center items-center">
    <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-semibold">Apply Store Credit</h2>
            <button type="button" class="close-apply-credit-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>
        </div>
        {{ html()->form()->attributes(['data-parsley-validate' => true, 'id' => 'applyCreditForm'])->open() }}
        <div class="flex flex-col gap-y-4 px-4 py-4">
            <div class="flex justify-between items-center px-4 py-3 bg-teal-50 border border-teal-200 rounded-lg text-sm">
                <span class="text-gray-600">Available Credit</span>
                <span class="font-bold text-teal-700" id="applyAvailableDisplay">{{ \App\Helpers\CustomHelper::formatCurrency($customerCreditSummary['balance']) }}</span>
            </div>
            <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                <span class="text-gray-600">Remaining Order Balance</span>
                <span class="font-bold text-gray-800" id="applyOrderBalanceDisplay">{{ \App\Helpers\CustomHelper::formatCurrency($remainingOrderBalance) }}</span>
            </div>
            <div class="flex gap-2">
                <button type="button" id="applyFullBalanceBtn" class="flex-1 px-3 py-2 rounded-md border border-teal-500 text-teal-700 text-sm font-medium hover:bg-teal-50">Apply Full Balance</button>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Amount</label>
                <div class="flex items-center border border-gray-300 rounded-md px-3 py-2">
                    <span class="text-gray-500 text-sm mr-1">$</span>
                    <input type="number" step="0.01" min="0.01" name="amount" id="applyCreditAmount" class="flex-1 text-sm focus:outline-none" placeholder="0.00" required data-parsley-required="true" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible</label>
                {!! html()->select('responsible_person', $employees->pluck('full_name', 'id')->prepend('Select Person Responsible', ''))->id('applyCreditResponsiblePerson')->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->required() !!}
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                {!! html()->textarea('reason', null)->id('applyCreditReason')->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->rows(2)->placeholder('Defaults to "Applied to Order #' . $order->order_number . '"') !!}
            </div>
        </div>
        <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
            <button type="button" class="close-apply-credit-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
            <button type="submit" class="px-6 py-2 rounded-md bg-teal-600 text-white font-medium hover:bg-teal-700 shadow-sm">Apply</button>
        </div>
        </form>
    </div>
</div>
@endcan

@can('customer_credit.grant')
<div id="removeCreditModalWrapper" style="display:none" class="fixed inset-0 z-[99999] overflow-y-auto bg-gray-500/75 flex justify-center items-center">
    <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-semibold">Remove Applied Credit</h2>
            <button type="button" class="close-remove-credit-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none">&times;</button>
        </div>
        {{ html()->form()->attributes(['data-parsley-validate' => true, 'id' => 'removeCreditForm'])->open() }}
        <div class="flex flex-col gap-y-4 px-4 py-4">
            <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                <span class="text-gray-600">Currently Applied to This Order</span>
                <span class="font-bold text-gray-800" id="removeAppliedDisplay">{{ \App\Helpers\CustomHelper::formatCurrency($orderAppliedCredit) }}</span>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Amount to Remove</label>
                <div class="flex items-center border border-gray-300 rounded-md px-3 py-2">
                    <span class="text-gray-500 text-sm mr-1">$</span>
                    <input type="number" step="0.01" min="0.01" name="amount" id="removeCreditAmount" class="flex-1 text-sm focus:outline-none" placeholder="0.00" required data-parsley-required="true" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible</label>
                {!! html()->select('responsible_person', $employees->pluck('full_name', 'id')->prepend('Select Person Responsible', ''))->id('removeCreditResponsiblePerson')->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->required() !!}
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                {!! html()->textarea('reason', null)->id('removeCreditReason')->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700')->rows(2)->placeholder('Defaults to "Reversed — Applied to Order #' . $order->order_number . '"') !!}
            </div>
        </div>
        <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
            <button type="button" class="close-remove-credit-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
            <button type="submit" class="px-6 py-2 rounded-md bg-red-600 text-white font-medium hover:bg-red-700 shadow-sm">Remove</button>
        </div>
        </form>
    </div>
</div>
@endcan

@can('customer_credit.view')
<script>
(function () {
    const panel = document.getElementById('customerCreditPanel');
    if (!panel) return;

    const orderUniqueId = '{{ $order->unique_id }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function fmtCurrency(n) {
        return '$' + Number(n).toFixed(2);
    }

    function wireModal(openBtnId, wrapperId, closeBtnClass) {
        const openBtn = document.getElementById(openBtnId);
        const wrapper = document.getElementById(wrapperId);
        if (!openBtn || !wrapper) return;
        openBtn.addEventListener('click', () => { wrapper.style.display = 'flex'; });
        wrapper.querySelectorAll('.' + closeBtnClass).forEach(btn => {
            btn.addEventListener('click', () => { wrapper.style.display = 'none'; });
        });
    }

    wireModal('openApplyCreditModal', 'applyCreditModalWrapper', 'close-apply-credit-modal-btn');
    wireModal('openRemoveCreditModal', 'removeCreditModalWrapper', 'close-remove-credit-modal-btn');

    const applyFullBtn = document.getElementById('applyFullBalanceBtn');
    if (applyFullBtn) {
        applyFullBtn.addEventListener('click', () => {
            const available = parseFloat(panel.dataset.availableBalance);
            const orderBalance = Math.max(0, parseFloat(panel.dataset.orderBalanceDue) - parseFloat(panel.dataset.appliedToOrder));
            document.getElementById('applyCreditAmount').value = Math.min(available, orderBalance).toFixed(2);
        });
    }

    function submitJson(form, endpoint) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        apiFetch(endpoint, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData,
        }).then(res => {
            if (res && res.success) {
                notyf.success(res.message);
                window.location.reload();
            } else {
                notyf.error((res && res.message) || 'Something went wrong.');
            }
        }).finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    const applyForm = document.getElementById('applyCreditForm');
    if (applyForm) {
        applyForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!$(applyForm).parsley().isValid()) {
                $(applyForm).parsley().validate();
                return;
            }
            submitJson(applyForm, "{{ route('admin.order-management.orders.customer-credit.apply', ':unique_id') }}".replace(':unique_id', orderUniqueId));
        });
    }

    const removeForm = document.getElementById('removeCreditForm');
    if (removeForm) {
        removeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!$(removeForm).parsley().isValid()) {
                $(removeForm).parsley().validate();
                return;
            }
            submitJson(removeForm, "{{ route('admin.order-management.orders.customer-credit.remove', ':unique_id') }}".replace(':unique_id', orderUniqueId));
        });
    }
})();
</script>
@endcan

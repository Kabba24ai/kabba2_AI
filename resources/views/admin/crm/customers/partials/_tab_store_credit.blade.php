{{--
    Phase 3.1 — Customer Credit Administration.

    Internal only. No customer-facing functionality, no Order Entry/checkout
    integration — per that phase's explicit scope. Data comes entirely from
    $customerCreditSummary / $customerCreditHistory, both prepared by
    Admin\Crm\Customers\ViewController via CustomerCreditService — this
    partial contains no balance arithmetic of its own.
--}}

<div class="p-6 space-y-6">

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Current Credit Balance</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">${{ number_format($customerCreditSummary['balance'], 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Lifetime Credit Granted</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">${{ number_format($customerCreditSummary['lifetime_granted'], 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Lifetime Credit Redeemed</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">${{ number_format($customerCreditSummary['lifetime_redeemed'], 2) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Current Available Credit</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">${{ number_format($customerCreditSummary['balance'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Same as Current Credit Balance today — Financial Credit only, no expiration yet.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 opacity-60">
            <p class="text-xs font-medium text-gray-500 uppercase">Pending Credits</p>
            <p class="text-lg font-medium text-gray-400 mt-1">—</p>
            <p class="text-xs text-gray-400 mt-1">Future support placeholder — not implemented in this phase.</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 opacity-60">
            <p class="text-xs font-medium text-gray-500 uppercase">Expiring Credits</p>
            <p class="text-lg font-medium text-gray-400 mt-1">—</p>
            <p class="text-xs text-gray-400 mt-1">Placeholder only — no expiration engine exists yet (architecture only, per CUSTOMER_CREDIT_ARCHITECTURE.md).</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Credit Status</p>
            <p class="mt-1">
                @if ($customerCreditSummary['balance'] > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active Balance</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">No Balance</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-2">
        @can('customer_credit.grant')
            <a href="javascript:void(0)" id="openGrantCreditModal" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg text-md">
                Grant Credit
            </a>
        @endcan
        @can('customer_credit.redeem')
            <a href="javascript:void(0)" id="openRedeemCreditModal" class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-3 rounded-lg text-md">
                Redeem Credit
            </a>
        @endcan
    </div>

    {{-- History --}}
    @can('customer_credit.view_audit_history')
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input type="text" id="ccHistorySearch" placeholder="Reason, notes, user..." class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select id="ccHistoryTypeFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="grant">Credit Granted</option>
                        <option value="redemption">Credit Redeemed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="date" id="ccHistoryDateFrom" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="date" id="ccHistoryDateTo" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <button type="button" id="ccHistoryClearFilters" class="text-sm text-gray-500 underline">Clear filters</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="ccHistoryTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="cc-sortable px-4 py-2 text-left font-medium text-gray-500 cursor-pointer" data-sort="date">Date</th>
                            <th class="cc-sortable px-4 py-2 text-left font-medium text-gray-500 cursor-pointer" data-sort="type">Transaction Type</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Credit Granted</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Credit Redeemed</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Running Balance</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">User</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Source</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Reason</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customerCreditHistory as $entry)
                            <tr class="cc-history-row border-t border-gray-100"
                                data-date="{{ $entry['date'] }}"
                                data-type="{{ $entry['type'] }}"
                                data-search="{{ strtolower($entry['reason'].' '.$entry['notes'].' '.$entry['user']) }}">
                                <td class="px-4 py-2 text-gray-600">{{ $entry['date_display'] }}</td>
                                <td class="px-4 py-2">
                                    @if ($entry['type'] === 'grant')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Credit Granted</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Credit Redeemed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-green-700">{{ $entry['type'] === 'grant' ? '$'.number_format($entry['amount'], 2) : '—' }}</td>
                                <td class="px-4 py-2 text-right text-amber-700">{{ $entry['type'] === 'redemption' ? '$'.number_format($entry['amount'], 2) : '—' }}</td>
                                <td class="px-4 py-2 text-right font-medium text-gray-900">${{ number_format($entry['running_balance'], 2) }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $entry['user'] }}</td>
                                <td class="px-4 py-2 text-gray-600">Financial Credit</td>
                                <td class="px-4 py-2 text-gray-600">{{ $entry['reason'] }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $entry['notes'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-gray-400">No Customer Credit activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan
</div>

{{-- Grant Credit Modal --}}
@can('customer_credit.grant')
<div id="grantCreditModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <h2 class="text-lg font-medium text-gray-900">Grant Credit</h2>
                <button type="button" id="closeGrantCreditModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div class="px-6 overflow-y-auto">
                {{ html()->form('POST', route('admin.crm.customers.customer-credit.grant'))->id('grantCreditForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-6',
                ])->open() }}

                {!! html()->text('customer_id', $customer->id ?? '')->class('hidden') !!}

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Amount</label>
                    <div class="relative">
                        <span class="absolute h-[36px] inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        {!! html()->text('amount', old('amount'))->attributes([
                            'placeholder' => '0.00', 'autocomplete' => 'off', 'data-parsley-min' => '0.01', 'min' => '0.01',
                        ])->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')->required() !!}
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Reason</label>
                    {!! html()->select('reason', [
                        '' => 'Select reason',
                        'Overpayment' => 'Overpayment',
                        'Refund Converted to Credit' => 'Refund Converted to Credit',
                        'Warranty Adjustment' => 'Warranty Adjustment',
                        'Settlement Adjustment' => 'Settlement Adjustment',
                        'Rental Adjustment' => 'Rental Adjustment',
                        'Manager Goodwill' => 'Manager Goodwill',
                    ], old('reason'))->class('w-full border border-gray-300 rounded-md px-3 py-3')->required() !!}
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date</label>
                    {!! html()->date('effective_date', old('effective_date'))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm') !!}
                    <p class="text-xs text-gray-400 mt-1">Informational only — does not change ledger ordering.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible</label>
                    {!! html()->select('responsible_person',
                        $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                        old('responsible_person')
                    )->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')->required() !!}
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    {!! html()->textarea('notes', old('notes'))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')->rows(2) !!}
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Internal Comments</label>
                    {!! html()->textarea('internal_comments', old('internal_comments'))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')->rows(2)->attributes(['placeholder' => 'Staff-only — never shown to the customer']) !!}
                </div>

                <div class="flex justify-end gap-2 pb-4">
                    <button type="button" id="cancelGrantCreditBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">Cancel</button>
                    <button type="submit" id="submitGrantCreditBtn" class="relative px-6 py-3 text-md rounded-lg bg-teal-600 text-white flex items-center justify-center gap-2">
                        <span id="grantCreditBtnText">Grant Credit</span>
                        <svg id="grantCreditBtnSpinner" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>

                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</div>
@endcan

{{-- Redeem Credit Modal --}}
@can('customer_credit.redeem')
<div id="redeemCreditModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <h2 class="text-lg font-medium text-gray-900">Redeem Credit</h2>
                <button type="button" id="closeRedeemCreditModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div class="px-6 overflow-y-auto">
                <p class="text-sm text-gray-500 mb-2">Available balance: <strong>${{ number_format($customerCreditSummary['balance'], 2) }}</strong>. Manual, internal administration only — no checkout integration.</p>

                {{ html()->form('POST', route('admin.crm.customers.customer-credit.redeem'))->id('redeemCreditForm')->attributes([
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'space-y-6',
                ])->open() }}

                {!! html()->text('customer_id', $customer->id ?? '')->class('hidden') !!}

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Amount</label>
                    <div class="relative">
                        <span class="absolute h-[36px] inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                        {!! html()->text('amount', old('amount'))->attributes([
                            'placeholder' => '0.00', 'autocomplete' => 'off', 'data-parsley-min' => '0.01',
                            'data-parsley-max' => (float) $customerCreditSummary['balance'], 'min' => '0.01',
                        ])->class('pl-7 pr-3 py-3 w-full border border-gray-300 rounded-md text-sm')->required() !!}
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Reason</label>
                    {!! html()->text('reason', old('reason'))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')->required() !!}
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Person Responsible</label>
                    {!! html()->select('responsible_person',
                        $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select person responsible', '')->toArray(),
                        old('responsible_person')
                    )->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')->required() !!}
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    {!! html()->textarea('notes', old('notes'))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')->rows(2) !!}
                </div>

                <div class="flex justify-end gap-2 pb-4">
                    <button type="button" id="cancelRedeemCreditBtn" class="px-6 py-3 text-md rounded-lg border border-gray-300 bg-white text-gray-700">Cancel</button>
                    <button type="submit" id="submitRedeemCreditBtn" class="relative px-6 py-3 text-md rounded-lg bg-gray-700 text-white flex items-center justify-center gap-2">
                        <span id="redeemCreditBtnText">Redeem Credit</span>
                        <svg id="redeemCreditBtnSpinner" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </button>
                </div>

                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
</div>
@endcan

<script>
(function () {
    // Modal open/close — self-contained, does not depend on any script
    // defined in a different tab's partial.
    function wireModal(openBtnId, wrapperId, closeBtnId, cancelBtnId) {
        const openBtn = document.getElementById(openBtnId);
        const wrapper = document.getElementById(wrapperId);
        if (!openBtn || !wrapper) return;
        const close = () => { wrapper.style.display = 'none'; };
        openBtn.addEventListener('click', () => { wrapper.style.display = 'flex'; });
        document.getElementById(closeBtnId)?.addEventListener('click', close);
        document.getElementById(cancelBtnId)?.addEventListener('click', close);
    }
    wireModal('openGrantCreditModal', 'grantCreditModalWrapper', 'closeGrantCreditModalBtn', 'cancelGrantCreditBtn');
    wireModal('openRedeemCreditModal', 'redeemCreditModalWrapper', 'closeRedeemCreditModalBtn', 'cancelRedeemCreditBtn');

    function attachValidatedSubmit(formId, btnId, btnTextId, spinnerId, loadingText) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (window.jQuery && $(form).parsley) {
                if (!$(form).parsley().isValid()) return;
            }
            const btn = document.getElementById(btnId);
            document.getElementById(btnTextId).textContent = loadingText;
            document.getElementById(spinnerId).classList.remove('hidden');
            btn.disabled = true;
            form.submit();
        });
    }
    attachValidatedSubmit('grantCreditForm', 'submitGrantCreditBtn', 'grantCreditBtnText', 'grantCreditBtnSpinner', 'Granting...');
    attachValidatedSubmit('redeemCreditForm', 'submitRedeemCreditBtn', 'redeemCreditBtnText', 'redeemCreditBtnSpinner', 'Redeeming...');

    // Local history search/filter/sort — deliberately client-side, per
    // PHASE_3_1_ADMIN_AUDIT.md §6 (a single customer's history does not
    // warrant a server round-trip the way a cross-customer list would).
    const table = document.getElementById('ccHistoryTable');
    if (!table) return;
    const rows = Array.from(table.querySelectorAll('.cc-history-row'));
    const searchInput = document.getElementById('ccHistorySearch');
    const typeFilter = document.getElementById('ccHistoryTypeFilter');
    const dateFrom = document.getElementById('ccHistoryDateFrom');
    const dateTo = document.getElementById('ccHistoryDateTo');

    function applyFilters() {
        const term = (searchInput.value || '').toLowerCase();
        const type = typeFilter.value;
        const from = dateFrom.value;
        const to = dateTo.value;
        rows.forEach(row => {
            let visible = true;
            if (term && !row.dataset.search.includes(term)) visible = false;
            if (type && row.dataset.type !== type) visible = false;
            if (from && row.dataset.date < from) visible = false;
            if (to && row.dataset.date > to) visible = false;
            row.style.display = visible ? '' : 'none';
        });
    }
    [searchInput, typeFilter, dateFrom, dateTo].forEach(el => el.addEventListener('input', applyFilters));

    document.getElementById('ccHistoryClearFilters').addEventListener('click', () => {
        searchInput.value = '';
        typeFilter.value = '';
        dateFrom.value = '';
        dateTo.value = '';
        applyFilters();
    });

    let sortDirection = {};
    document.querySelectorAll('.cc-sortable').forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sort;
            sortDirection[key] = !sortDirection[key];
            const tbody = table.querySelector('tbody');
            const sorted = rows.slice().sort((a, b) => {
                const av = a.dataset[key] || '';
                const bv = b.dataset[key] || '';
                return sortDirection[key] ? av.localeCompare(bv) : bv.localeCompare(av);
            });
            sorted.forEach(row => tbody.appendChild(row));
        });
    });
})();
</script>

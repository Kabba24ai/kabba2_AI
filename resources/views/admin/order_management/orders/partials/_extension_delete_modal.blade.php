{{--
    Delete Extension Transaction — shared confirmation modal.

    An extension child order and its Rental Extension charge are ONE
    transaction; both deletion entry points (child-order Delete and the
    Billing Engine row Delete) open this modal and post to their own
    endpoint, which routes through ExtensionTransactionService.

    Requires: $employees (active users for the Processed By select).
    JS API:   window.extDeleteFlow.open({ contextHtml, paystate, url, payload, onSuccess })
--}}
<div id="extDeleteModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200 overflow-hidden flex flex-col max-h-full">
        {{-- Header --}}
        <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center">
                    <x-heroicon-o-trash class="w-4 h-4 text-red-600" />
                </span>
                <h2 class="text-lg font-medium text-gray-900">Delete Extension Transaction?</h2>
            </div>
            <button type="button" id="extDeleteCloseBtn"
                class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-4 overflow-y-auto flex flex-col gap-4">
            <div id="extDeleteContext" class="text-sm text-gray-700 leading-relaxed"></div>

            <div id="extDeletePayState" class="text-xs"></div>

            {{-- Administrative disposition — required for paid transactions
                 with no Kabba-recorded refund or void --}}
            <div id="extDeleteDisposition" class="hidden flex-col gap-3 border-t border-gray-100 pt-4">
                <div class="text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                    This extension is <span class="font-bold">paid</span> with no refund or void recorded in Kabba.
                    Confirm how the money was handled before deleting.
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 required">Reason</label>
                    <select id="extDeleteReason"
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-700">
                        <option value="">Select a reason…</option>
                        @foreach (\App\Enums\Orders\ExtensionDeletionReason::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="extDeleteReasonOtherWrap" class="hidden">
                    <label class="text-sm font-medium text-gray-700 required">Describe the reason</label>
                    <input type="text" id="extDeleteReasonOther" maxlength="255"
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm"
                        placeholder="What happened to this payment?" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700 required">Processed By</label>
                        <select id="extDeleteProcessedBy"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-700">
                            <option value="">Select employee…</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 required">Employee ID</label>
                        <input type="text" id="extDeleteEmployeeCode" maxlength="20" inputmode="numeric"
                            autocomplete="off"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm"
                            placeholder="Confirm your Employee ID" />
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="extDeleteNotes" rows="2" maxlength="1000"
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm"
                        placeholder="Optional — reference numbers, gateway confirmation, etc."></textarea>
                </div>
            </div>

            <div id="extDeleteError" class="hidden text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2"></div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50">
            <button type="button" id="extDeleteCancelBtn"
                class="px-5 py-2.5 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                Cancel
            </button>
            <button type="button" id="extDeleteConfirmBtn"
                class="px-5 py-2.5 rounded-lg font-medium text-sm bg-red-600 text-white hover:bg-red-700 shadow-sm transition">
                Delete Both Records
            </button>
        </div>
    </div>
</div>

<script>
    window.extDeleteFlow = (function () {
        const modal       = document.getElementById('extDeleteModal');
        const context     = document.getElementById('extDeleteContext');
        const payState    = document.getElementById('extDeletePayState');
        const disposition = document.getElementById('extDeleteDisposition');
        const reasonSel   = document.getElementById('extDeleteReason');
        const otherWrap   = document.getElementById('extDeleteReasonOtherWrap');
        const otherInput  = document.getElementById('extDeleteReasonOther');
        const processedBy = document.getElementById('extDeleteProcessedBy');
        const empCode     = document.getElementById('extDeleteEmployeeCode');
        const notes       = document.getElementById('extDeleteNotes');
        const errorBox    = document.getElementById('extDeleteError');
        const confirmBtn  = document.getElementById('extDeleteConfirmBtn');

        let current = null;

        function close() {
            modal.classList.replace('flex', 'hidden');
            current = null;
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        function needsDisposition() {
            return current && current.paystate === 'paid_unresolved';
        }

        reasonSel.addEventListener('change', function () {
            otherWrap.classList.toggle('hidden', this.value !== 'other');
        });

        document.getElementById('extDeleteCloseBtn').addEventListener('click', close);
        document.getElementById('extDeleteCancelBtn').addEventListener('click', close);

        confirmBtn.addEventListener('click', function () {
            if (!current) return;

            errorBox.classList.add('hidden');
            const payload = Object.assign({}, current.payload || {});

            if (needsDisposition()) {
                if (!reasonSel.value) { showError('Please select a reason.'); return; }
                if (reasonSel.value === 'other' && !otherInput.value.trim()) { showError('Please describe the reason.'); return; }
                if (!processedBy.value) { showError('Please select the responsible employee.'); return; }
                if (!empCode.value.trim()) { showError('Please confirm the Employee ID.'); return; }

                payload.reason        = reasonSel.value;
                payload.reason_other  = otherInput.value.trim() || null;
                payload.processed_by  = processedBy.value;
                payload.employee_code = empCode.value.trim();
                payload.notes         = notes.value.trim() || null;
            }

            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Deleting…';

            fetch(current.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    const done = current.onSuccess;
                    close();
                    if (done) done(data);
                    return;
                }
                if (data && data.errors) {
                    showError(Object.values(data.errors).map(v => v.join(' ')).join(' '));
                } else {
                    showError((data && data.message) || 'Failed to delete the extension transaction.');
                }
                if (data && data.requires_disposition) {
                    disposition.classList.remove('hidden');
                    disposition.classList.add('flex');
                }
            })
            .catch(() => showError('Request failed. Please try again.'))
            .finally(() => {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete Both Records';
            });
        });

        return {
            open(cfg) {
                current = cfg;

                context.innerHTML = cfg.contextHtml;

                const stateLabels = {
                    unpaid:          ['Payment status: Unpaid', 'text-amber-700 bg-amber-50 border-amber-200'],
                    paid_settled:    ['Payment status: Paid — refund/void recorded in Kabba', 'text-blue-700 bg-blue-50 border-blue-200'],
                    paid_unresolved: ['Payment status: Paid — no refund or void recorded', 'text-red-700 bg-red-50 border-red-200'],
                };
                const state = stateLabels[cfg.paystate] || null;
                if (state) {
                    payState.className = 'text-xs font-semibold border rounded-md px-3 py-2 ' + state[1];
                    payState.textContent = state[0];
                    payState.classList.remove('hidden');
                } else {
                    payState.classList.add('hidden');
                }

                const showDisposition = cfg.paystate === 'paid_unresolved';
                disposition.classList.toggle('hidden', !showDisposition);
                disposition.classList.toggle('flex', showDisposition);

                reasonSel.value = '';
                otherWrap.classList.add('hidden');
                otherInput.value = '';
                processedBy.value = '';
                empCode.value = '';
                notes.value = '';
                errorBox.classList.add('hidden');

                modal.classList.replace('hidden', 'flex');
            },
        };
    })();
</script>

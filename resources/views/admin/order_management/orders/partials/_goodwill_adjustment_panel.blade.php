@php
    // GOODWILL — a manager-authorised PRE-TAX concession, never a payment.
    //
    // ── EVERY FIGURE COMES FROM THE SERVER ────────────────────────────────
    // This template computes no money. The breakdown is fetched from the
    // preview endpoint, which reads it from the shared pre-tax engine, so an
    // operator can never be shown a number the writer would recompute
    // differently.
    //
    // ── VISIBILITY IS NOT AUTHORITY ───────────────────────────────────────
    // Both checks below decide only whether to OFFER the action. The service
    // re-checks authority and every eligibility rule under a row lock, and is
    // the sole authority. Hiding the trigger is a courtesy to the operator,
    // not a security control.
    //
    // ── WHY NOT @can ──────────────────────────────────────────────────────
    // AppServiceProvider registers Gate::before(fn () => true), so @can and
    // the permission: middleware consent for every signed-in user. The direct
    // Spatie helper is the only check that still refuses. It fails closed and
    // logs when the permission is unseeded, so a deployment that has not run
    // the seeder renders a page WITHOUT the action rather than an error.
    $goodwillUser       = auth()->user();
    $goodwillCanApply   = \App\Services\Goodwill\GoodwillPermissions::canApply($goodwillUser);
    $goodwillCanReverse = \App\Services\Goodwill\GoodwillPermissions::canReverse($goodwillUser);

    $goodwillActive = \App\Models\Goodwill\OrderGoodwillAdjustment::activeFor((int) $order->id);

    // Eligibility is answered by the service, not re-implemented here — a view
    // that judged it for itself would drift from the writer the first time a
    // rule changed.
    $goodwillAvailable = $goodwillCanApply
        && $goodwillActive === null
        && app(\App\Services\Goodwill\GoodwillAdjustmentService::class)->isAvailableFor($order);

    $goodwillApprovers = $goodwillAvailable
        ? \App\Services\Goodwill\GoodwillPermissions::approvers()
        : collect();
@endphp

{{-- ── Trigger: only on a partly-paid order, only for an authorised user ── --}}
@if ($goodwillAvailable)
    <button type="button" id="goodwillOpenBtn"
        class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600 transition-colors">
        <x-heroicon-o-hand-raised class="w-3.5 h-3.5" />
        Apply Goodwill
    </button>
@endif

{{-- ── Applied state: summary + reversal, for directly authorised users ── --}}
@if ($goodwillActive)
    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-amber-800 bg-amber-100 rounded-full">
        Goodwill - Pre-Tax · {{ \App\Helpers\CustomHelper::formatCurrency($goodwillActive->concessionAmount() ?? 0) }}
        <span class="ml-1.5 font-normal text-amber-700">{{ $goodwillActive->reason_code?->label() }}</span>
    </span>

    @if ($goodwillCanReverse)
        <button type="button" id="goodwillReverseBtn"
            data-url="{{ route('admin.order-management.orders.goodwill.reverse', [$order->unique_id, $goodwillActive->id]) }}"
            class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-xs font-semibold border border-amber-300 bg-white text-amber-800 hover:bg-amber-50 transition-colors">
            <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
            Reverse Goodwill
        </button>
    @endif
@endif

@if ($goodwillAvailable)
<div id="goodwillModal" style="display:none" class="fixed inset-0 z-[99999] items-center justify-center bg-gray-900/40"
     x-data="goodwillAdjustment({
        previewUrl: '{{ route('admin.order-management.orders.goodwill.preview', $order->unique_id) }}',
        applyUrl: '{{ route('admin.order-management.orders.goodwill.apply', $order->unique_id) }}',
        defaultApprover: {{ (int) (auth()->id() ?? 0) }},
     })">
    <div class="bg-white rounded-xl border border-gray-200 shadow-xl w-[560px] max-w-[94vw] max-h-[92vh] flex flex-col overflow-hidden">

        <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
            <x-heroicon-o-hand-raised class="w-5 h-5 text-amber-600" />
            <h3 class="text-[15px] font-semibold text-gray-900">Apply Goodwill Adjustment</h3>
            <button type="button" data-gw-close title="Close"
                class="ml-auto text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        <div class="px-5 py-4 flex flex-col gap-4 overflow-y-auto">

            {{-- The business distinction, stated before any figure. --}}
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-[13px] text-amber-900">
                <span class="font-semibold">Goodwill changes the order total. It does not record another payment.</span>
                The customer's payment stays exactly as collected; the order is reduced to meet it.
            </div>

            <p x-show="loading" class="text-sm text-gray-500">Calculating…</p>
            <p x-show="error" x-text="error" class="text-sm text-error-600 whitespace-pre-line"></p>

            <template x-if="breakdown && !error">
                <div class="flex flex-col gap-4">

                    {{-- ── The sequence, exactly as approved ────────────── --}}
                    <div class="rounded-lg border border-gray-200 divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between px-3.5 py-2">
                            <span class="text-gray-600">Amount Collected</span>
                            <span class="font-medium text-gray-900" x-text="money(breakdown.amount_collected)"></span>
                        </div>
                        <div class="flex justify-between px-3.5 py-2">
                            <span class="text-gray-600">Current Balance</span>
                            <span class="font-medium text-gray-900" x-text="money(breakdown.remaining_balance)"></span>
                        </div>
                        <div class="flex justify-between px-3.5 py-2 bg-amber-50/60">
                            <span class="text-amber-900 font-semibold">Goodwill - Pre-Tax</span>
                            <span class="font-semibold text-amber-900" x-text="'-' + money(breakdown.goodwill_amount)"></span>
                        </div>

                        {{-- Only when there is one, and never folded into the
                             concession above. --}}
                        <template x-if="breakdown.rounding_residual != 0">
                            <div class="flex justify-between px-3.5 py-2">
                                <span class="text-gray-600">Rounding Residual</span>
                                <span class="font-medium text-gray-900" x-text="money(breakdown.rounding_residual)"></span>
                            </div>
                        </template>

                        <div class="flex justify-between px-3.5 py-2">
                            <span class="text-gray-600">Discounted Product Value</span>
                            <span class="font-medium text-gray-900" x-text="money(breakdown.discounted_product_value)"></span>
                        </div>
                        <div class="flex justify-between px-3.5 py-2">
                            <span class="text-gray-600">Sales Tax</span>
                            <span class="font-medium text-gray-900" x-text="money(breakdown.sales_tax)"></span>
                        </div>

                        <template x-if="breakdown.special_tax != 0">
                            <div class="flex justify-between px-3.5 py-2">
                                <span class="text-gray-600">Special Tax</span>
                                <span class="font-medium text-gray-900" x-text="money(breakdown.special_tax)"></span>
                            </div>
                        </template>

                        <template x-if="breakdown.added_fees != 0">
                            <div class="flex justify-between px-3.5 py-2">
                                <span class="text-gray-600">Added Fees</span>
                                <span class="font-medium text-gray-900" x-text="money(breakdown.added_fees)"></span>
                            </div>
                        </template>

                        <div class="flex justify-between px-3.5 py-2.5 bg-gray-50">
                            <span class="font-semibold text-gray-900">Revised Order Total</span>
                            <span class="font-semibold text-gray-900" x-text="money(breakdown.revised_grand_total)"></span>
                        </div>
                    </div>

                    <template x-if="breakdown.rounding_residual != 0">
                        <p class="text-xs text-gray-500">
                            The revised total cannot land exactly on the amount collected, so
                            <span x-text="money(breakdown.rounding_residual)"></span> remains as an
                            overpayment. It is recorded on the adjustment rather than absorbed into
                            the Goodwill amount.
                        </p>
                    </template>

                    {{-- ── Authorisation ────────────────────────────────── --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Reason <span class="text-error-600">*</span></label>
                            <select x-model="reason"
                                class="h-10 w-full rounded-lg border border-gray-300 px-2.5 text-sm focus:outline-none focus:ring-4 focus:ring-amber-500/15">
                                <option value="">Select a reason…</option>
                                @foreach (\App\Enums\Goodwill\GoodwillReason::groupedByCategory() as $categoryValue => $reasons)
                                    <optgroup label="{{ \App\Enums\Goodwill\GoodwillReasonCategory::from($categoryValue)->label() }}">
                                        @foreach ($reasons as $reason)
                                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Authorising Manager <span class="text-error-600">*</span></label>
                            <select x-model.number="approvedBy"
                                class="h-10 w-full rounded-lg border border-gray-300 px-2.5 text-sm focus:outline-none focus:ring-4 focus:ring-amber-500/15">
                                <option value="0">Select a manager…</option>
                                @foreach ($goodwillApprovers as $approver)
                                    <option value="{{ $approver->id }}">{{ trim($approver->first_name.' '.$approver->last_name) }}</option>
                                @endforeach
                            </select>
                            @if ($goodwillApprovers->isEmpty())
                                <p class="text-xs text-error-600 mt-1">No user currently holds Goodwill approval.</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            Note <span x-show="reason === 'OTHER'" class="text-error-600">* required for “Other”</span>
                        </label>
                        <textarea x-model="note" rows="2" maxlength="1000"
                            class="w-full rounded-lg border border-gray-300 px-2.5 py-2 text-sm focus:outline-none focus:ring-4 focus:ring-amber-500/15"
                            placeholder="What happened, and what was agreed."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" data-gw-close
                            class="h-9 px-3.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="apply()" :disabled="!canApply() || busy"
                            class="inline-flex items-center h-9 px-3.5 rounded-lg text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 disabled:opacity-50">
                            <span x-show="!busy">Apply Goodwill</span>
                            <span x-show="busy">Applying…</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endif

@push('js')
<script>
    function goodwillAdjustment(cfg) {
        return {
            ...cfg,
            loading: false,
            busy: false,
            error: null,
            breakdown: null,
            expectedGoodwillAmount: null,
            expectedAcceptedPaymentTotal: null,
            reason: '',
            note: '',
            approvedBy: 0,
            // One token per modal opening, reused across retries of THAT
            // submission — so a double-click or a lost response returns the
            // original decision instead of granting a second concession.
            idempotencyToken: null,

            money(value) {
                return '$' + Number(value ?? 0).toFixed(2);
            },

            csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content;
            },

            async load() {
                this.loading = true;
                this.error = null;
                this.breakdown = null;
                this.idempotencyToken = (crypto.randomUUID && crypto.randomUUID()) || String(Date.now());

                try {
                    const res = await fetch(this.previewUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        this.error = data.message || 'Goodwill is not available for this order.';
                    } else {
                        this.breakdown = data.breakdown;
                        this.expectedGoodwillAmount = data.expected_goodwill_amount;
                        this.expectedAcceptedPaymentTotal = data.expected_accepted_payment_total;
                        this.approvedBy = this.defaultApprover;
                    }
                } catch (e) {
                    this.error = 'Could not calculate the Goodwill amount. Please try again.';
                }

                this.loading = false;
            },

            canApply() {
                if (!this.breakdown || !this.reason || !this.approvedBy) return false;
                if (this.reason === 'OTHER' && this.note.trim() === '') return false;
                return true;
            },

            async apply() {
                if (!this.canApply()) return;
                this.busy = true;
                this.error = null;

                try {
                    const res = await fetch(this.applyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf(),
                        },
                        body: JSON.stringify({
                            reason_code: this.reason,
                            note: this.note || null,
                            approved_by: this.approvedBy,
                            // Both echoed back exactly as previewed. The server
                            // re-derives them under a row lock and refuses if
                            // either has moved.
                            expected_goodwill_amount: this.expectedGoodwillAmount,
                            expected_accepted_payment_total: this.expectedAcceptedPaymentTotal,
                            idempotency_token: this.idempotencyToken,
                        }),
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        this.error = data.message || 'Could not apply the Goodwill adjustment.';
                        this.busy = false;
                        return;
                    }

                    window.location.reload();
                } catch (e) {
                    this.error = 'Something went wrong. Nothing was applied.';
                    this.busy = false;
                }
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('goodwillModal');
        const openBtn = document.getElementById('goodwillOpenBtn');

        if (modal && openBtn) {
            openBtn.addEventListener('click', function () {
                modal.style.display = 'flex';
                // Figures are fetched on every opening, never cached from a
                // previous view of the page.
                (window.Alpine ? Alpine.$data(modal) : modal.__x?.$data)?.load();
            });

            modal.querySelectorAll('[data-gw-close]').forEach(function (el) {
                el.addEventListener('click', function () { modal.style.display = 'none'; });
            });
        }

        const reverseBtn = document.getElementById('goodwillReverseBtn');

        if (reverseBtn) {
            reverseBtn.addEventListener('click', async function () {
                const reason = window.prompt(
                    'Reversing re-opens this order\'s balance. Why is the Goodwill adjustment being withdrawn?'
                );

                if (reason === null) return;

                if (reason.trim().length < 3) {
                    alert('A reason is required to reverse a Goodwill adjustment.');
                    return;
                }

                reverseBtn.disabled = true;

                try {
                    const res = await fetch(reverseBtn.dataset.url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        body: JSON.stringify({ reason: reason }),
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        alert(data.message || 'Could not reverse the Goodwill adjustment.');
                        reverseBtn.disabled = false;
                        return;
                    }

                    window.location.reload();
                } catch (e) {
                    alert('Something went wrong. Nothing was reversed.');
                    reverseBtn.disabled = false;
                }
            });
        }
    });
</script>
@endpush

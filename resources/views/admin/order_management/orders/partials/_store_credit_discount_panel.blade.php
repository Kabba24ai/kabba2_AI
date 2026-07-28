@php
    // Store Credit is a PRE-TAX DISCOUNT here — never a payment/tender.
    //
    // Every value shown when a discount is applied is read straight from the
    // canonical outputs of the discount engine — the ProductDiscount snapshot
    // ($scAppliedDiscount) and the order columns the engine rewrote
    // (tax_amount / grand_total). This view performs NO tender arithmetic
    // (no "grand_total - store_credit"); it only consumes engine-produced
    // figures. See app/Services/Discounts/Targets/OrderDiscountTarget.php.
    $scSummary = $customerCreditSummary ?? ['balance' => 0, 'lifetime_granted' => 0, 'lifetime_redeemed' => 0];

    $scAvailable = $order->customer_id
        ? (float) \App\Services\CustomerCreditService::remainingBalance((int) $order->customer_id)
        : 0.0;

    $scAppliedDiscount = \App\Models\Discounts\ProductDiscount::query()
        ->where('target_type', 'order')->where('target_id', $order->id)
        ->where('discount_type', 'store_credit')->where('status', 'applied')
        ->latest('id')->first();

    $scIsApplied = (bool) $scAppliedDiscount;

    // Canonical read model (engine outputs only) — used when a discount exists.
    $scOriginalValue   = $scIsApplied ? (float) $scAppliedDiscount->original_product_value  : (float) $order->subtotal;
    $scDiscountAmount  = $scIsApplied ? (float) $scAppliedDiscount->calculated_discount_amount : 0.0;
    $scDiscountedValue = $scIsApplied ? (float) $scAppliedDiscount->discounted_product_value : (float) $order->subtotal;
    $scRecalcTax       = $scIsApplied ? (float) $scAppliedDiscount->tax_after : (float) $order->tax_amount;
    $scNewGrandTotal   = (float) $order->grand_total; // engine-rewritten, the real balance basis
    // Any remaining order components (delivery/other fees) so the 5 canonical
    // lines always reconcile to the real grand total.
    $scOtherCharges    = $scIsApplied ? round($scNewGrandTotal - $scDiscountedValue - $scRecalcTax, 2) : 0.0;
@endphp

<div class="bg-white p-4 rounded shadow border border-gray-200 mt-6"
     x-data="storeCreditDiscount({
        applyUrl: '{{ route('admin.order-management.orders.store-credit-discount.apply', $order->unique_id) }}',
        available: {{ $scAvailable }},
        original: {{ (float) $order->subtotal }},
        rate: {{ (float) (\App\Services\ChargeTaxCalculator::currentRate()) }},
        responsible: {{ (int) (auth()->id() ?? 0) }},
        alreadyApplied: {{ $scDiscountAmount }},
     })">
    <div class="flex items-center gap-2 mb-3">
        <x-heroicon-o-gift class="w-5 h-5 text-emerald-600" />
        <h3 class="text-base font-semibold text-gray-900">Store Credit Discount</h3>
        <span class="text-xs text-gray-500">(pre-tax product discount — not a payment)</span>
    </div>

    {{-- Read-only credit balance context (folded in from the retired Customer Credit panel). --}}
    @can('customer_credit.view')
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Available Store Credit</div>
                <div class="text-lg font-bold text-emerald-700">{{ \App\Helpers\CustomHelper::formatCurrency($scAvailable) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Lifetime Credit Granted</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($scSummary['lifetime_granted']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Lifetime Credit Redeemed</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($scSummary['lifetime_redeemed']) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">Current Credit Balance</div>
                <div class="text-lg font-bold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($scSummary['balance']) }}</div>
            </div>
        </div>
    @endcan

    @if ($scIsApplied)
        {{-- Applied state: canonical DiscountResult summary (engine values only). --}}
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-4 space-y-1 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Original Product Value</span>
                <span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($scOriginalValue) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Store Credit Discount</span>
                <span class="font-medium text-emerald-700">-{{ \App\Helpers\CustomHelper::formatCurrency($scDiscountAmount) }}</span>
            </div>
            <div class="flex justify-between border-t border-emerald-200 pt-1">
                <span class="text-gray-600">Discounted Product Value</span>
                <span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($scDiscountedValue) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Recalculated Sales Tax</span>
                <span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($scRecalcTax) }}</span>
            </div>
            @if ($scOtherCharges > 0.005)
                <div class="flex justify-between">
                    <span class="text-gray-600">Other Charges (fees)</span>
                    <span class="font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($scOtherCharges) }}</span>
                </div>
            @endif
            <div class="flex justify-between border-t border-emerald-200 pt-1 font-bold text-gray-900">
                <span>New Grand Total</span>
                <span>{{ \App\Helpers\CustomHelper::formatCurrency($scNewGrandTotal) }}</span>
            </div>
        </div>
        <p class="text-xs text-gray-400 mb-3">Remaining balance due equals the recalculated grand total above and is collected through the normal payment flow.</p>

        @can('customer_credit.grant')
            <button type="button" @click="remove($event)" :disabled="busy"
                data-remove-url="{{ route('admin.order-management.orders.store-credit-discount.remove', ['unique_id' => $order->unique_id, 'discountId' => $scAppliedDiscount->id]) }}"
                class="px-4 py-2 rounded-lg text-sm font-semibold border border-red-300 text-red-700 hover:bg-red-50 disabled:opacity-50">
                <span x-show="!busy">Remove Store Credit Discount</span>
                <span x-show="busy">Removing…</span>
            </button>
            <p class="text-xs text-red-600 mt-1" x-show="error" x-text="error"></p>
        @endcan
    @else
        {{-- Unapplied state: show the original value + an apply control. --}}
        <div class="grid grid-cols-2 gap-y-1 text-sm mb-1">
            <span class="text-gray-500">Original Product Value</span>
            <span class="text-right font-medium">{{ \App\Helpers\CustomHelper::formatCurrency($order->subtotal) }}</span>
            <span class="text-gray-500">Available Store Credit</span>
            <span class="text-right font-medium text-emerald-700">{{ \App\Helpers\CustomHelper::formatCurrency($scAvailable) }}</span>
        </div>

        @can('customer_credit.redeem')
            @if ($scAvailable > 0 && (float) $order->balance_due > 0)
                <div class="mt-4 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Store Credit to Apply</label>
                        <input type="number" step="0.01" min="0.01" x-model.number="amount"
                            class="w-full rounded-lg border-gray-300 text-sm" placeholder="0.00">
                    </div>
                    <button type="button" @click="apply()" :disabled="!canApply() || busy"
                        class="px-4 py-2 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                        <span x-show="!busy">Apply Store Credit Discount</span>
                        <span x-show="busy">Applying…</span>
                    </button>
                </div>
                <p class="text-xs text-red-600 mt-1" x-show="error" x-text="error"></p>
                <p class="text-xs text-gray-400 mt-1">Cannot exceed available Store Credit ($<span x-text="available.toFixed(2)"></span>) or the product value. The remaining balance is collected through the normal payment flow.</p>
            @elseif ($scAvailable <= 0)
                <p class="text-sm text-gray-500 mt-3">This customer has no Store Credit available to apply.</p>
            @endif
        @endcan
    @endif
</div>

@push('js')
<script>
    function storeCreditDiscount(cfg) {
        return {
            ...cfg,
            amount: null,
            busy: false,
            error: null,
            canApply() {
                const a = parseFloat(this.amount);
                const remainingBase = this.original - this.alreadyApplied;
                return a > 0 && a <= this.available && a <= remainingBase;
            },
            async apply() {
                if (!this.canApply()) { this.error = 'Enter a valid amount within available Store Credit and the product value.'; return; }
                this.busy = true; this.error = null;
                try {
                    const res = await fetch(this.applyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        body: JSON.stringify({
                            store_credit_discount: this.amount,
                            responsible_person: this.responsible,
                            request_uuid: (crypto.randomUUID && crypto.randomUUID()) || String(Date.now()),
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) { this.error = data.message || 'Could not apply Store Credit.'; this.busy = false; return; }
                    window.location.reload();
                } catch (e) {
                    this.error = 'Something went wrong. Please try again.';
                    this.busy = false;
                }
            },
            async remove($event) {
                const url = ($event?.currentTarget || $event?.target)?.dataset?.removeUrl
                    || document.querySelector('[data-remove-url]')?.dataset?.removeUrl;
                if (!url) { this.error = 'Missing remove target.'; return; }
                this.busy = true; this.error = null;
                try {
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        body: JSON.stringify({
                            responsible_person: this.responsible,
                            request_uuid: (crypto.randomUUID && crypto.randomUUID()) || String(Date.now()),
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) { this.error = data.message || 'Could not remove Store Credit discount.'; this.busy = false; return; }
                    window.location.reload();
                } catch (e) {
                    this.error = 'Something went wrong. Please try again.';
                    this.busy = false;
                }
            },
        };
    }
</script>
@endpush

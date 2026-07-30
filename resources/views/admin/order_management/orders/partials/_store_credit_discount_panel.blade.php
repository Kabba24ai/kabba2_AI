@php
    // Store Credit is a PRE-TAX DISCOUNT — never a payment/tender. This partial
    // is now a small value-entry modal (opened from the header "Store Credit"
    // trigger); the applied result is injected straight into the order Subtotal
    // window by edit.blade.php using the engine's own ProductDiscount snapshot.
    // No tender arithmetic anywhere (no "grand_total - store_credit").
    $scAvailable = $order->customer_id
        ? (float) \App\Services\CustomerCreditService::remainingBalance((int) $order->customer_id)
        : 0.0;

    $scAppliedDiscount = \App\Models\Discounts\ProductDiscount::query()
        ->where('target_type', 'order')->where('target_id', $order->id)
        ->where('discount_type', 'store_credit')->where('status', 'applied')
        ->latest('id')->first();

    $scIsApplied      = (bool) $scAppliedDiscount;
    $scDiscountAmount = $scIsApplied ? (float) $scAppliedDiscount->calculated_discount_amount : 0.0;
@endphp

@can('customer_credit.redeem')
{{-- Apply Store Credit modal — opened by the header gift trigger (data-sc-open).
     Value-entry only; the summary line + Remove button live in the order
     Subtotal window. Toggled via style.display, mirroring the PO modal. --}}
<div id="storeCreditModal" style="display:none" class="fixed inset-0 z-[99999] items-center justify-center bg-gray-900/40"
     x-data="storeCreditDiscount({
        applyUrl: '{{ route('admin.order-management.orders.store-credit-discount.apply', $order->unique_id) }}',
        available: {{ $scAvailable }},
        original: {{ (float) $order->subtotal }},
        rate: {{ (float) (\App\Services\ChargeTaxCalculator::currentRate()) }},
        responsible: {{ (int) (auth()->id() ?? 0) }},
        alreadyApplied: {{ $scDiscountAmount }},
     })">
    <div class="bg-white rounded-xl border border-gray-200 shadow-xl w-[400px] max-w-[92vw] flex flex-col overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
            <x-heroicon-o-gift class="w-5 h-5 text-brand-600" />
            <h3 class="text-[15px] font-semibold text-gray-900">Apply Store Credit</h3>
            <button type="button" data-sc-close title="Close"
                class="ml-auto text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        <div class="px-5 py-4 flex flex-col gap-4">
            {{-- Read-only available balance context. --}}
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3.5 flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-[0.06em] text-gray-500">Available Store Credit</span>
                <span class="text-[19px] font-semibold text-brand-700">{{ \App\Helpers\CustomHelper::formatCurrency($scAvailable) }}</span>
            </div>

            @if ($scIsApplied)
                {{-- Already applied: keep it single-discount and simple — remove it
                     from the order summary to change the amount. --}}
                <p class="text-sm text-gray-600">
                    A Store Credit discount of
                    <span class="font-semibold text-brand-700">{{ \App\Helpers\CustomHelper::formatCurrency($scDiscountAmount) }}</span>
                    is already applied to this order. To change it, remove it from the order summary first.
                </p>
                <div class="flex justify-end">
                    <button type="button" data-sc-close
                        class="h-9 px-3.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            @elseif ($scAvailable > 0 && (float) $order->balance_due > 0)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Store Credit to Apply</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">$</span>
                        <input type="number" step="0.01" min="0.01" x-model.number="amount"
                            class="h-10 w-full rounded-lg border border-gray-300 pl-7 pr-3 text-sm focus:outline-none focus:ring-4 focus:ring-brand-500/15 focus:border-brand-300"
                            placeholder="0.00">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Cannot exceed available Store Credit ($<span x-text="available.toFixed(2)"></span>) or the product value.
                        The remaining balance is collected through the normal payment flow.
                    </p>
                    <p class="text-xs text-error-600 mt-1" x-show="error" x-text="error"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" data-sc-close
                        class="h-9 px-3.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" @click="apply()" :disabled="!canApply() || busy"
                        class="inline-flex items-center h-9 px-3.5 rounded-lg text-sm font-semibold bg-brand-500 text-white hover:bg-brand-700 disabled:opacity-50">
                        <span x-show="!busy">Apply Store Credit</span>
                        <span x-show="busy">Applying…</span>
                    </button>
                </div>
            @else
                <p class="text-sm text-gray-500">This customer has no Store Credit available to apply.</p>
                <div class="flex justify-end">
                    <button type="button" data-sc-close
                        class="h-9 px-3.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            @endif
        </div>
    </div>
</div>
@endcan

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

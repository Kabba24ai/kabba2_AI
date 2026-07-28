@php
    // Store Credit is a PRE-TAX DISCOUNT here — never a payment/tender.
    $scAvailable = $order->customer_id
        ? (float) \App\Services\CustomerCreditService::remainingBalance((int) $order->customer_id)
        : 0.0;
    $scOriginalValue = (float) $order->subtotal;
    $scApplied = (float) ($order->pretax_discount_total ?? 0);
    $scAppliedDiscount = \App\Models\Discounts\ProductDiscount::query()
        ->where('target_type', 'order')->where('target_id', $order->id)
        ->where('discount_type', 'store_credit')->where('status', 'applied')
        ->latest('id')->first();
@endphp

<div class="bg-white p-4 rounded shadow border border-gray-200 mt-6"
     x-data="storeCreditDiscount({
        applyUrl: '{{ route('admin.order-management.orders.store-credit-discount.apply', $order->unique_id) }}',
        available: {{ $scAvailable }},
        original: {{ $scOriginalValue }},
        rate: {{ (float) (\App\Services\ChargeTaxCalculator::currentRate()) }},
        responsible: {{ (int) (auth()->id() ?? 0) }},
        alreadyApplied: {{ $scApplied }},
     })">
    <div class="flex items-center gap-2 mb-3">
        <x-heroicon-o-gift class="w-5 h-5 text-emerald-600" />
        <h3 class="text-base font-semibold text-gray-900">Store Credit Discount</h3>
        <span class="text-xs text-gray-500">(pre-tax product discount — not a payment)</span>
    </div>

    @if ($scAvailable <= 0 && $scApplied <= 0)
        <p class="text-sm text-gray-500">This customer has no Store Credit available to apply.</p>
    @else
        <div class="grid grid-cols-2 gap-y-1 text-sm">
            <span class="text-gray-500">Original Product Value</span>
            <span class="text-right font-medium">${{ number_format($scOriginalValue, 2) }}</span>

            <span class="text-gray-500">Available Store Credit</span>
            <span class="text-right font-medium text-emerald-700">${{ number_format($scAvailable, 2) }}</span>

            @if ($scApplied > 0)
                <span class="text-gray-500">Store Credit Applied</span>
                <span class="text-right font-medium">-${{ number_format($scApplied, 2) }}</span>

                <span class="text-gray-500">Adjusted Product Value</span>
                <span class="text-right font-medium">${{ number_format(max(0, $scOriginalValue - $scApplied), 2) }}</span>

                <span class="text-gray-500">Sales Tax</span>
                <span class="text-right font-medium">${{ number_format((float) $order->tax_amount, 2) }}</span>

                <span class="text-gray-700 font-semibold">Final Amount Due</span>
                <span class="text-right font-semibold">${{ number_format((float) $order->balance_due, 2) }}</span>
            @endif
        </div>

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
        @endif
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
        };
    }
</script>
@endpush

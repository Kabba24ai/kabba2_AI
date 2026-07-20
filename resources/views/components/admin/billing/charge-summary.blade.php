@props([
    // Unique per usage — element ids become "{prefix}-summary-base" etc.
    'idPrefix' => 'bcs',
    // Display-only percentage label, e.g. "9.75"
    'taxPercentage' => '0',
])

{{-- Billing Engine — the official charge summary card (Billing Engine UI
     refinement). One recognizable financial-impact block for EVERY billable
     charge (extensions, fuel, damage, future types): Base / Tax / Total,
     updated live by window.BillingSummary.update(). Modeled on the Add
     Extension Charge modal's summary, promoted to a shared component with
     a deliberate emerald accent so the money always stands out. The values
     shown are a client-side preview only — the server (ChargeTaxCalculator
     + updateCreditBalance) remains authoritative at save time. --}}
<div class="bg-emerald-50/60 rounded-lg p-3 border border-emerald-200 space-y-1 text-sm">
    <div class="flex justify-between text-gray-600">
        <span>Base Amount:</span>
        <span id="{{ $idPrefix }}-summary-base">$0.00</span>
    </div>
    <div class="flex justify-between text-gray-600">
        <span>Sales Tax (<span id="{{ $idPrefix }}-summary-rate">{{ $taxPercentage }}</span>%):</span>
        <span id="{{ $idPrefix }}-summary-tax">$0.00</span>
    </div>
    <div class="flex justify-between font-bold text-gray-800 border-t border-emerald-200 pt-2 mt-1">
        <span>Total:</span>
        <span id="{{ $idPrefix }}-summary-total">$0.00</span>
    </div>
</div>

@once
<script>
(function () {
    'use strict';

    const money = (n) => '$' + (Number.isFinite(n) ? n : 0).toFixed(2);

    /**
     * Shared live-summary math for every Billing Engine charge form.
     * treatment: 'add' (entered = base, tax on top) | 'free' (no tax)
     *          | 'reverse' (entered amount already includes tax).
     * Mirrors ChargeTaxCalculator's three canonical treatments — preview
     * only; the server recomputes authoritatively on save.
     */
    window.BillingSummary = {
        update(prefix, enteredAmount, treatment, rate) {
            const entered = Number(enteredAmount) || 0;
            let base, tax;

            if (treatment === 'add') {
                base = entered;
                tax = Math.round(entered * rate * 100) / 100;
            } else if (treatment === 'reverse') {
                base = Math.round((entered / (1 + rate)) * 100) / 100;
                tax = Math.round((entered - base) * 100) / 100;
            } else {
                base = entered;
                tax = 0;
            }

            document.getElementById(`${prefix}-summary-base`).textContent = money(base);
            document.getElementById(`${prefix}-summary-tax`).textContent = money(tax);
            document.getElementById(`${prefix}-summary-total`).textContent = money(base + tax);
        },
    };
})();
</script>
@endonce

@props([
    'id',
    'placeholder' => '0.00',
])

{{-- Billing Engine — the canonical currency-entry control (Billing Charge
     Operations polish). Cents-based typing: digits shift through the
     decimal automatically (1 → 0.01, 1248 → 12.48), so an employee aiming
     for $12.48 can never accidentally submit $1,248.00. Pasted values are
     normalized ($1,234.56 → 1234.56); negatives are impossible by
     construction. The field's .value is always a plain decimal string
     ("12.48"), so existing consumers (BillingSummary, payload builders)
     read it unchanged — and the SERVER's decimal validation remains the
     authority, this control is convenience only.
     inputmode="numeric" raises the digit keyboard on mobile. --}}
<div class="flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-blue-500">
    <span class="text-gray-500 text-sm mr-1">$</span>
    <input type="text" inputmode="numeric" autocomplete="off" id="{{ $id }}" data-billing-currency
           class="flex-1 text-sm focus:outline-none" placeholder="{{ $placeholder }}">
</div>

@once
<script>
(function () {
    'use strict';

    const MAX_CENTS_DIGITS = 10; // $99,999,999.99 — server rules still apply

    function digitsToDisplay(digits) {
        if (!digits) return '';
        const cents = parseInt(digits, 10) || 0;
        return cents ? (cents / 100).toFixed(2) : '';
    }

    function caretToEnd(el) {
        const len = el.value.length;
        try { el.setSelectionRange(len, len); } catch (e) { /* unfocused */ }
    }

    function attach(el) {
        if (el.dataset.bcAttached) return;
        el.dataset.bcAttached = '1';
        let reformatting = false;

        // Typing model: the field is a stream of cent digits. Reformat on
        // every input; backspace naturally walks backward through the cents.
        // The re-dispatched input event lets other listeners (e.g. the live
        // Billing Summary) see the FORMATTED value.
        el.addEventListener('input', () => {
            if (reformatting) return;
            const digits = el.value.replace(/\D/g, '').slice(0, MAX_CENTS_DIGITS);
            const display = digitsToDisplay(digits);
            if (el.value !== display) {
                reformatting = true;
                el.value = display;
                caretToEnd(el);
                el.dispatchEvent(new Event('input', { bubbles: true }));
                reformatting = false;
            }
        });

        // Paste normalization: decimal-looking text is taken at face value
        // ("$1,234.56" → 1234.56); digit-only text follows the cents model.
        el.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData)?.getData('text') || '';
            const cleaned = text.replace(/[^0-9.]/g, '');
            let cents;
            if (cleaned.includes('.')) {
                cents = Math.round((parseFloat(cleaned) || 0) * 100);
            } else {
                cents = parseInt(cleaned.slice(0, MAX_CENTS_DIGITS) || '0', 10) || 0;
            }
            cents = Math.max(0, cents);
            reformatting = true;
            el.value = cents ? (cents / 100).toFixed(2) : '';
            el.dispatchEvent(new Event('input', { bubbles: true }));
            reformatting = false;
            caretToEnd(el);
        });

        el.addEventListener('focus', () => setTimeout(() => caretToEnd(el), 0));
    }

    window.BillingCurrency = { attach };

    document.addEventListener('DOMContentLoaded', () =>
        document.querySelectorAll('[data-billing-currency]').forEach(attach));
})();
</script>
@endonce

<?php

namespace App\Enums\Orders;

/**
 * WHERE a resolved historical tax basis was reconstructed from.
 *
 * Recorded on every successful resolution and logged, so a refund's tax
 * split can always be traced back to the evidence it was derived from
 * rather than being an unattributable number. The three sources are not
 * interchangeable — each carries a different strength of proof, and the
 * weakest one is deliberately the hardest to reach.
 *
 * {@see \App\Services\Orders\HistoricalTaxBasisResolver}
 */
enum HistoricalTaxBasisSource: string
{
    /**
     * Reconstructed from the order's own order_products rows. The normal,
     * strongest case: per-line frozen basis and tax, reconciled against the
     * order's stored totals.
     */
    case OrderProductLines = 'order_product_lines';

    /**
     * Reconstructed from an extension child order's linked billing_charges
     * record. Extension children own no order_products by design, but the
     * BillingEngine bridge writes an authoritative charge carrying the
     * extension's amount, tax_amount and tax_type.
     */
    case ExtensionBillingCharge = 'extension_billing_charge';

    /**
     * An extension child order's own stored totals, used ONLY when the
     * linked charge is genuinely absent (a legacy row, or the best-effort
     * BillingEngine bridge having failed and only logged) AND every
     * extension invariant is proven.
     *
     * This is NOT a general line-less-order fallback and must never become
     * one: an extension has exactly one tax posture for the whole order, so
     * no tax-free line can hide inside its subtotal. Any order that cannot
     * prove that property is rejected instead.
     */
    case ExtensionOrderLevel = 'extension_order_level';

    public function label(): string
    {
        return match ($this) {
            self::OrderProductLines      => 'Order product lines',
            self::ExtensionBillingCharge => 'Extension billing charge',
            self::ExtensionOrderLevel    => 'Extension order-level totals',
        };
    }
}

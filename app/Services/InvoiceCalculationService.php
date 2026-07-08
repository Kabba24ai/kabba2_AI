<?php

namespace App\Services;

use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Invoice;

/**
 * Financial Engine — invoice calculations (Phase 2.4).
 *
 * TRANSACTION-SAFE. Invoices are financial transaction records, not
 * analytics (see docs/financial-engine-consolidation/
 * PHASE_2_3A_CALCULATION_PATTERN_ARCHITECTURE.md §8) — every method on
 * this class produces or reflects the recorded, source-of-truth value of
 * one specific invoice. This class does not call TaxCalculationService's
 * analytics-only methods, and should never be extended to.
 *
 * recomputeSummary() does not call TaxCalculationService at all, even for
 * the transaction-safe methods: invoice line items (`invoice_items.tax`)
 * already store a computed dollar amount, not a rate — unlike
 * `customer_accounts.sales_tax`, which is a rate (see
 * docs/crm-billing-audit/CRM_BILLING_PAYMENT_AUDIT.md §4.6 on this exact
 * unit mismatch). There is no rate-based calculation to centralize here;
 * this method is pure aggregation of already-computed line-item values.
 *
 * recomputeSummary()'s body is moved verbatim from the pre-Phase-2.4
 * `CustomHelper::updateInvoiceSummary()` — no formula, rounding, or
 * business-rule change. `CustomHelper::updateInvoiceSummary()` now
 * delegates here so its existing callers (CustomerAccount\UpdateController,
 * CustomerAccount\DeleteController, Invoice\UpdateController,
 * Invoice\DeleteInvoiceController, BulkDeleteController,
 * RepairDeletedOrdersController) continue to work unchanged. The two
 * invoice payment controllers (Admin\Crm\Customers\Invoice\PaymentStoreController,
 * Front\Customer\Dashboard\Invoice\PaymentStoreController) were migrated
 * in this same phase to call this method directly instead of their
 * previous inline paid_amount/open_amount/invoice_status arithmetic — see
 * docs/financial-engine-consolidation/PHASE_2_4_COMPLETION_REPORT.md for
 * the full migration and equivalence proof.
 */
class InvoiceCalculationService
{
    /**
     * Recompute an invoice's subtotal, tax, total, paid amount, open
     * amount, and status from its current line items and the payment
     * ledger, then save.
     *
     * paid_amount is always recomputed as the full sum of this invoice's
     * `payment`-type CustomerAccount rows — not incremented — so this
     * method is safe to call any time the ledger or line items may have
     * changed, and self-corrects any prior drift between the invoice's
     * stored total and its actual line items/payments.
     */
    public static function recomputeSummary(Invoice $invoice): void
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        $totalRefund = 0;

        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            $price = (float) ($item->unit ?? 0);
            $tax = (float) ($item->tax ?? 0);

            // charge + order
            if (in_array($item->type, ['charge', 'order'])) {
                $subtotal += $price;
                $totalTax += $tax;
            }

            // discount
            if ($item->type === 'discount') {
                $totalDiscount += abs($price);
                $totalTax -= abs($tax);
            }

            // refund
            if ($item->type === 'refund') {
                $totalRefund += abs($price);
                $totalTax -= abs($tax);
            }
        }

        $subtotal = max(0, $subtotal);
        $totalTax = max(0, $totalTax);

        $finalTotal = $subtotal + $totalTax - $totalDiscount - $totalRefund;
        $finalTotal = max(0, $finalTotal);

        $invoice->subtotal = round($subtotal, 2);
        $invoice->sales_tax = round($totalTax, 2);
        $invoice->total = round($finalTotal, 2);

        /*
        |--------------------------------------------------------------------------
        | Recalculate Payments
        |--------------------------------------------------------------------------
        */

        $paidAmount = CustomerAccount::where(
            'invoice_id',
            $invoice->id
        )
            ->where('type', 'payment')
            ->sum('amount');

        $paidAmount = abs((float) $paidAmount);

        $openAmount = max(
            $finalTotal - $paidAmount,
            0
        );

        /*
        |--------------------------------------------------------------------------
        | Invoice Status
        |--------------------------------------------------------------------------
        */

        if ($openAmount <= 0) {
            $invoiceStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $invoiceStatus = 'partial_paid';
        } else {
            $invoiceStatus = 'pending';
        }

        $invoice->paid_amount = round(
            $paidAmount,
            2
        );

        $invoice->open_amount = round(
            $openAmount,
            2
        );

        $invoice->invoice_status = $invoiceStatus;

        $invoice->save();
    }
}

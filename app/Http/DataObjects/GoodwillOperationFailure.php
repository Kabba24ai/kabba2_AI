<?php

namespace App\Http\DataObjects;

/**
 * WHY an apply() or reverse() was refused.
 *
 * Separate from GoodwillCalculationFailure, which says the arithmetic could
 * not be produced. These say the arithmetic was fine but the OPERATION is not
 * permitted — authority, state, concurrency, or safety. Every refusal is
 * named so no caller can mistake "refused" for "nothing happened".
 */
enum GoodwillOperationFailure: string
{
    case PermissionDenied       = 'permission_denied';
    case ReasonNoteRequired     = 'reason_note_required';
    case ActiveAdjustmentExists = 'active_adjustment_exists';
    case StaleOrderState        = 'stale_order_state';
    case CalculationFailed      = 'calculation_failed';
    case ProtectedLineUnsupported = 'protected_line_unsupported';
    case AdjustmentNotFound     = 'adjustment_not_found';
    case AlreadyReversed        = 'already_reversed';
    case ReversalUnsafe         = 'reversal_unsafe';

    // ── Durable downstream artifacts ───────────────────────────────────────
    // An order already represented in Accounts Receivable, on an issued
    // invoice, or on a printed receipt has been reported to someone outside
    // this order. Altering the order underneath such a document does not
    // correct it — it makes the two disagree silently. Goodwill refuses; the
    // correction belongs in a credit-memo / account-adjustment workflow that
    // amends the artifact explicitly.
    case AccountsReceivableAlreadyPosted = 'accounts_receivable_already_posted';
    case InvoiceAlreadyIssued            = 'invoice_already_issued';
    case ReceiptAlreadyIssued            = 'receipt_already_issued';

    case ReversalBlockedByAccountsReceivable = 'reversal_blocked_by_accounts_receivable';
    case ReversalBlockedByInvoice            = 'reversal_blocked_by_invoice';
    case ReversalBlockedByReceipt            = 'reversal_blocked_by_receipt';

    public function message(): string
    {
        return match ($this) {
            self::PermissionDenied => 'You do not have permission to apply or reverse a Goodwill adjustment.',
            self::ReasonNoteRequired => 'A note is required when the reason is "Other".',
            self::ActiveAdjustmentExists => 'This order already has an active Goodwill adjustment. Reverse it before applying another.',
            self::StaleOrderState => 'This order changed since the preview was calculated. Reload and try again.',
            self::CalculationFailed => 'The Goodwill adjustment could not be calculated for this order.',
            self::ProtectedLineUnsupported => 'This order contains a protected fee-type line, which the writer does not yet support.',
            self::AdjustmentNotFound => 'No Goodwill adjustment was found for this order.',
            self::AlreadyReversed => 'This Goodwill adjustment has already been reversed.',
            self::ReversalUnsafe => 'This Goodwill adjustment cannot be safely reversed because later financial activity depends on the adjusted totals.',

            self::AccountsReceivableAlreadyPosted => 'This order has already been posted to the customer\'s account. Reducing the order now would leave the receivable and the running balance stating a different amount than the order. Handle it through a credit memo or account adjustment, which amends the ledger explicitly, rather than by altering the order underneath it.',
            self::InvoiceAlreadyIssued => 'This order is on an issued invoice. Reducing the order now would leave the invoice, its open amount, and anything the customer is paying against it stating a different amount. Issue a credit memo against the invoice instead of altering the order underneath it.',
            // TEMPORARY — removed in Commit 4B once supersession is atomic with the adjustment.
            self::ReceiptAlreadyIssued => 'A receipt has already been created for this order, and receipt supersession is not available yet. Applying Goodwill now would leave that receipt permanently current and stale.',

            self::ReversalBlockedByAccountsReceivable => 'This adjustment cannot be reversed: the order was posted to the customer\'s account after the adjustment was applied, and restoring the original totals would contradict that receivable. Resolve the account entry first.',
            self::ReversalBlockedByInvoice => 'This adjustment cannot be reversed: an invoice was issued against this order after the adjustment was applied, and restoring the original totals would contradict it. Resolve the invoice first.',
            self::ReversalBlockedByReceipt => 'This adjustment cannot be reversed: a receipt was created after the adjustment was applied and would state the adjusted totals. Resolve the receipt first.',
        };
    }
}

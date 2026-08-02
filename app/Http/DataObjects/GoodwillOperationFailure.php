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
        };
    }
}

<?php

namespace App\Enums\ChecklistManagement;

/**
 * The LIFECYCLE of a Rental Ready inspection record — deliberately separate
 * from its RESULT (see RentalReadyResult). Lifecycle answers "can this row
 * still be edited / is it authoritative?"; result answers "what did the
 * inspection conclude?".
 *
 * Immutability rule (Phase 2A): only a `Draft` row may be reused/updated by a
 * later save. Every other state is terminal and frozen — a genuinely new
 * inspection always gets a NEW row (new unique_id). Readers treat only
 * `Completed` (and not `Voided`) rows as authoritative.
 */
enum RentalReadyLifecycleStatus: string
{
    case Draft = 'draft';           // in progress; reusable; not authoritative
    case Completed = 'completed';   // finalized with a definitive result; immutable; authoritative
    case Voided = 'voided';         // finalized then invalidated; immutable; NOT authoritative
    case Superseded = 'superseded'; // reserved: replaced by a newer completed inspection
    case Abandoned = 'abandoned';   // reserved: a draft explicitly discarded

    /** Terminal states may never be reused for a later inspection. */
    public function isTerminal(): bool
    {
        return $this !== self::Draft;
    }

    /** Only Completed (non-voided) rows are authoritative for equipment readiness. */
    public function isAuthoritative(): bool
    {
        return $this === self::Completed;
    }
}

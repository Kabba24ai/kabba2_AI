<?php

namespace App\Enums\Service;

/**
 * The ticket section a media item belongs to. Distinct from
 * ServiceMediaCategory (which is finer-grained, e.g. Before/During/After
 * Repair all fall under the Repair stage) — this drives dedicated-disk
 * folder layout and the default retention class. New workflow stages
 * (e.g. a future Diagnostic UI) only need a new case here.
 */
enum ServiceMediaWorkflowStage: string
{
    case Complaint  = 'complaint';
    case Notes      = 'notes';
    case Diagnostic = 'diagnostic';
    case Repair     = 'repair';
    case Settlement = 'settlement';
    case Warranty   = 'warranty';
    case General     = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Complaint  => 'Complaint',
            self::Notes      => 'Notes',
            self::Diagnostic => 'Diagnostic',
            self::Repair     => 'Repair',
            self::Settlement => 'Settlement',
            self::Warranty   => 'Warranty',
            self::General    => 'General',
        };
    }

    /**
     * All classes currently behave identically — stored independently so a
     * future retention policy can diverge per stage without another schema
     * change.
     */
    public function defaultRetentionClass(): ServiceMediaRetentionClass
    {
        return match ($this) {
            self::Complaint  => ServiceMediaRetentionClass::ServiceEvidence,
            self::Notes      => ServiceMediaRetentionClass::ServiceNote,
            self::Diagnostic => ServiceMediaRetentionClass::Diagnostic,
            self::Repair     => ServiceMediaRetentionClass::Repair,
            self::Settlement => ServiceMediaRetentionClass::Settlement,
            self::Warranty   => ServiceMediaRetentionClass::Warranty,
            self::General    => ServiceMediaRetentionClass::General,
        };
    }
}

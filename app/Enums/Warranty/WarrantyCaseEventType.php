<?php

namespace App\Enums\Warranty;

enum WarrantyCaseEventType: string
{
    case Created      = 'created';
    case QueueChanged = 'queue_changed';
    case FeeUpdated   = 'fee_updated';
    // ST-4 (Phase 2) workflow milestones
    case OemSubmitted        = 'oem_submitted';
    case OemDecision         = 'oem_decision';
    case CustomerDecision    = 'customer_decision';
    case ReimbursementRecorded = 'reimbursement_recorded';

    public function label(): string
    {
        return match ($this) {
            self::Created                => 'Case Created',
            self::QueueChanged           => 'Queue Changed',
            self::FeeUpdated             => 'Diagnostic Fee Updated',
            self::OemSubmitted           => 'Submitted to Manufacturer',
            self::OemDecision            => 'OEM Decision Recorded',
            self::CustomerDecision       => 'Customer Decision Recorded',
            self::ReimbursementRecorded  => 'Reimbursement Recorded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created                => 'bg-green-400',
            self::QueueChanged           => 'bg-purple-500',
            self::FeeUpdated             => 'bg-teal-400',
            self::OemSubmitted           => 'bg-blue-500',
            self::OemDecision            => 'bg-indigo-500',
            self::CustomerDecision       => 'bg-teal-500',
            self::ReimbursementRecorded  => 'bg-emerald-500',
        };
    }
}

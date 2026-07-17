<?php

namespace App\Enums\Communication;

enum SmsBroadcastStatus: string
{
    case Draft                = 'draft';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Scheduled            = 'scheduled';
    case Sending              = 'sending';
    case Sent                 = 'sent';
    case PartiallySent        = 'partially_sent';
    case Failed               = 'failed';
    case Cancelled            = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft                => 'Draft',
            self::AwaitingConfirmation => 'Awaiting Confirmation',
            self::Scheduled            => 'Scheduled',
            self::Sending              => 'Sending',
            self::Sent                 => 'Sent',
            self::PartiallySent        => 'Partially Sent',
            self::Failed               => 'Failed',
            self::Cancelled            => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft                => 'bg-gray-100 text-gray-700 border-gray-200',
            self::AwaitingConfirmation => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Scheduled            => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Sending              => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::Sent                 => 'bg-green-50 text-green-700 border-green-200',
            self::PartiallySent        => 'bg-amber-50 text-amber-800 border-amber-300',
            self::Failed               => 'bg-red-50 text-red-700 border-red-200',
            self::Cancelled            => 'bg-gray-100 text-gray-500 border-gray-200',
        };
    }

    /** Broadcast may still be edited (message/audience/schedule). */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::AwaitingConfirmation, self::Scheduled], true);
    }

    /** Terminal outcome — the broadcast is part of the historical record. */
    public function isCompleted(): bool
    {
        return in_array($this, [self::Sent, self::PartiallySent, self::Failed, self::Cancelled], true);
    }

    /** Normal-interface permanent deletion allowed. */
    public function isDeletable(): bool
    {
        return in_array($this, [self::Draft, self::AwaitingConfirmation], true);
    }
}

<?php

namespace App\Enums\Service;

enum DiagnosticStatus: string
{
    case NotStarted  = 'not_started';
    case InProgress  = 'in_progress';
    case Completed   = 'completed';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted  => 'Not Started',
            self::InProgress  => 'In Progress',
            self::Completed   => 'Completed',
            self::NotRequired => 'Not Required',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted  => 'bg-gray-100 text-gray-600',
            self::InProgress  => 'bg-sky-100 text-sky-700',
            self::Completed   => 'bg-green-100 text-green-700',
            self::NotRequired => 'bg-gray-100 text-gray-400',
        };
    }

    /** Diagnostic phases that allow a responsibility decision. */
    public function allowsResponsibilityDecision(): bool
    {
        return in_array($this, [self::Completed, self::NotRequired], true);
    }
}

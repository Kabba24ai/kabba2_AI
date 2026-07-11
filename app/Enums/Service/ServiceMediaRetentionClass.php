<?php

namespace App\Enums\Service;

/**
 * Retention grouping for a media file. No retention behavior is
 * implemented today (all classes are kept indefinitely) — this exists so a
 * future administrative retention policy (configurable periods, automatic
 * cleanup, legal holds) can target a class without re-tagging existing data.
 */
enum ServiceMediaRetentionClass: string
{
    case ServiceEvidence = 'service_evidence';
    case ServiceNote     = 'service_note';
    case Diagnostic       = 'diagnostic';
    case Repair            = 'repair';
    case Settlement        = 'settlement';
    case Warranty          = 'warranty';
    case General            = 'general';

    public function label(): string
    {
        return match ($this) {
            self::ServiceEvidence => 'Service Evidence',
            self::ServiceNote     => 'Service Note',
            self::Diagnostic       => 'Diagnostic',
            self::Repair            => 'Repair',
            self::Settlement        => 'Settlement',
            self::Warranty          => 'Warranty',
            self::General            => 'General',
        };
    }
}

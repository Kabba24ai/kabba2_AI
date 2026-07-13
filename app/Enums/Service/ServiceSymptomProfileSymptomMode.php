<?php

namespace App\Enums\Service;

/**
 * Per-symptom override on top of a profile's included categories: pull in
 * one extra symptom from a category the profile doesn't otherwise include,
 * or drop one the profile's included categories would otherwise show.
 */
enum ServiceSymptomProfileSymptomMode: string
{
    case Include = 'include';
    case Exclude = 'exclude';

    public function label(): string
    {
        return match ($this) {
            self::Include => 'Addition',
            self::Exclude => 'Exclusion',
        };
    }
}

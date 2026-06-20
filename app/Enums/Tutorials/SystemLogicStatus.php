<?php

namespace App\Enums\Tutorials;

enum SystemLogicStatus: string
{
    case Active      = 'active';
    case UnderReview = 'under_review';
    case Deprecated  = 'deprecated';

    public function label(): string
    {
        return match($this) {
            self::Active      => 'Active',
            self::UnderReview => 'Under Review',
            self::Deprecated  => 'Deprecated',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active      => 'bg-green-100 text-green-700',
            self::UnderReview => 'bg-yellow-100 text-yellow-700',
            self::Deprecated  => 'bg-gray-100 text-gray-500 line-through',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->all();
    }
}

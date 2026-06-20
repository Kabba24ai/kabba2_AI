<?php

namespace App\Enums\Tutorials;

enum SystemLogicVisibility: string
{
    case InternalAdmin    = 'internal_admin';
    case CustomerVisible  = 'customer_visible';
    case DeveloperOnly    = 'developer_only';

    public function label(): string
    {
        return match($this) {
            self::InternalAdmin   => 'Internal Admin',
            self::CustomerVisible => 'Customer Visible',
            self::DeveloperOnly   => 'Developer Only',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::InternalAdmin   => 'bg-blue-100 text-blue-700',
            self::CustomerVisible => 'bg-purple-100 text-purple-700',
            self::DeveloperOnly   => 'bg-orange-100 text-orange-700',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->all();
    }
}

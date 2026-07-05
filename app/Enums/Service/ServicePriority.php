<?php

namespace App\Enums\Service;

enum ServicePriority: string
{
    case Emergency = 'emergency';
    case High      = 'high';
    case Normal    = 'normal';
    case Low       = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Emergency => 'Emergency',
            self::High      => 'High',
            self::Normal    => 'Normal',
            self::Low       => 'Low',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Emergency => 'bg-red-100 text-red-700 border border-red-200',
            self::High      => 'bg-orange-100 text-orange-700 border border-orange-200',
            self::Normal    => 'bg-blue-100 text-blue-700 border border-blue-200',
            self::Low       => 'bg-gray-100 text-gray-600 border border-gray-200',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Emergency => 1,
            self::High      => 2,
            self::Normal    => 3,
            self::Low       => 4,
        };
    }

    /** SQL FIELD() argument list matching sortOrder() — for query-level ordering. */
    public static function sqlOrder(): string
    {
        return "FIELD(priority, 'emergency', 'high', 'normal', 'low')";
    }
}

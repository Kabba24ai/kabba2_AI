<?php

namespace App\Support\Tasks;

/**
 * Canonical, deterministic colour theme for an employee across the Task
 * Center. Keying on the user id (not loop position) means the same employee
 * resolves to the same theme in the multi-person summary and the focused
 * single-person view, and never shifts when filtering changes lane order or
 * membership. Unassigned work gets a neutral slate theme.
 */
class EmployeeTheme
{
    /** color = header accent/border/avatar/badge; bg = header background. */
    private const PALETTE = [
        ['color' => '#0d9488', 'bg' => '#e6faf6'], // teal
        ['color' => '#7c3aed', 'bg' => '#f3e8ff'], // purple
        ['color' => '#b45309', 'bg' => '#fef3c7'], // amber
        ['color' => '#0369a1', 'bg' => '#e0f2fe'], // blue
        ['color' => '#be185d', 'bg' => '#fce7f3'], // rose
        ['color' => '#047857', 'bg' => '#d1fae5'], // emerald
        ['color' => '#4338ca', 'bg' => '#e0e7ff'], // indigo
    ];

    private const UNASSIGNED = ['color' => '#475569', 'bg' => '#eef2f7'];

    /** @return array{color:string,bg:string} */
    public static function for(?int $userId): array
    {
        if ($userId === null) {
            return self::UNASSIGNED;
        }

        return self::PALETTE[$userId % count(self::PALETTE)];
    }
}

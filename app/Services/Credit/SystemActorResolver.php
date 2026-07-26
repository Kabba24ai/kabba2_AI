<?php

namespace App\Services\Credit;

use App\Models\Iam\Personnel\User;
use Illuminate\Support\Str;

/**
 * Resolves the single, deliberately-isolated System automation actor used as
 * `created_by` for system-generated Task-Manager tasks when no human owner
 * applies (the missing-Primary-Billing-Admin fallback). An audit confirmed the
 * app has NO pre-existing canonical system actor or non-human attribution
 * convention to reuse, so this actor is introduced explicitly and safely.
 *
 * Isolation properties (see database/seeders):
 *  - status = 'Inactive' → blocked from login AND excluded from every
 *    User::active() assignment selector (assignee dropdowns, driver lists, …).
 *  - no roles, is_driver unset, unusable random password.
 *  - never used as `assigned_to_user_id`, only `created_by_user_id`.
 *  - the Employee Performance report is activity-gated (orders/charges), so a
 *    task-only actor never appears in productivity metrics.
 *
 * Idempotent by email — safe to call repeatedly; the seeder provisions the same
 * row up-front.
 */
class SystemActorResolver
{
    public const EMAIL = 'system@kabba.ai';

    public static function id(): int
    {
        return static::user()->id;
    }

    public static function user(): User
    {
        return User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'first_name' => 'System',
                'last_name'  => 'Automation',
                'status'     => 'Inactive',
                // Plain random string; the model's `hashed` password cast hashes
                // it once → an unusable password. Never assign roles here.
                'password'   => Str::random(48),
            ],
        );
    }
}

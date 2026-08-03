<?php

namespace App\Services\Goodwill;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Throwable;

/**
 * The only authority check Goodwill is permitted to use.
 *
 * ── WHY NOT `can()`, `@can`, OR THE `permission:` MIDDLEWARE ──────────────
 *
 * `AppServiceProvider::gatesRegistration()` registers
 * `Gate::before(fn () => true)`. Every ability check that routes through the
 * Gate therefore returns true for any signed-in user, application-wide. That
 * includes Spatie's own `PermissionMiddleware`, which calls `$user->canAny()`
 * — verified in vendor, not assumed. So `permission:goodwill.apply` on a route
 * and `@can('goodwill.apply')` in a template can refuse nobody.
 *
 * `hasPermissionTo()` comes from Spatie's `HasPermissions` trait and consults
 * the role/permission tables directly. It does not touch the Gate, and the
 * `User` model uses the trait unmodified. It is the one check that still means
 * something, and it is the only one used here.
 *
 * Route middleware and `@can` may still be present elsewhere as defence in
 * depth for the day the bypass is removed. Nothing may DEPEND on them.
 *
 * ── A MISSING PERMISSION IS A DENIAL, NOT A CRASH ─────────────────────────
 *
 * Spatie raises `PermissionDoesNotExist` when the named permission has never
 * been registered. Unhandled, an unseeded production database would turn every
 * authority check into a 500 rather than a refusal. It is caught and logged as
 * the deployment error it is, and treated as "no", because the alternative —
 * treating an unanswerable question as consent — is the failure mode that
 * matters on a screen that reduces revenue.
 */
final class GoodwillPermissions
{
    public const APPLY = 'goodwill.apply';
    public const REVERSE = 'goodwill.reverse';

    /** Every permission this feature depends on — the seeder reads this list. */
    public static function all(): array
    {
        return [
            self::APPLY => 'Apply Goodwill',
            self::REVERSE => 'Reverse Goodwill',
        ];
    }

    public static function canApply(?Authenticatable $user): bool
    {
        return self::holds($user, self::APPLY);
    }

    public static function canReverse(?Authenticatable $user): bool
    {
        return self::holds($user, self::REVERSE);
    }

    /** @throws GoodwillException */
    public static function assertCanApply(?Authenticatable $user): void
    {
        if (! self::canApply($user)) {
            throw new GoodwillException('You are not authorized to apply a Goodwill adjustment.');
        }
    }

    /** @throws GoodwillException */
    public static function assertCanReverse(?Authenticatable $user): void
    {
        if (! self::canReverse($user)) {
            throw new GoodwillException('You are not authorized to reverse a Goodwill adjustment.');
        }
    }

    /**
     * The authorizing manager must hold the apply permission in their own
     * right. Authority to receive a payment does not imply authority to reduce
     * what is owed, and the operator naming a manager is not evidence that the
     * manager has that authority.
     */
    public static function isValidApprover(?Authenticatable $user): bool
    {
        return self::canApply($user);
    }

    /**
     * Everyone who may authorize a Goodwill adjustment, for an approver picker.
     *
     * Queried straight from the permission tables — the Gate bypass would make
     * any ability-based filter return every user. Returns an EMPTY collection
     * when the permission has never been registered, so an unseeded deployment
     * renders a screen with no approvers rather than a 500. The fault is logged
     * once by the same path every other check uses.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function approvers()
    {
        try {
            return \App\Models\Iam\Personnel\User::permission(self::APPLY)
                ->orderBy('first_name')
                ->get();
        } catch (PermissionDoesNotExist) {
            Log::error("Goodwill permission '".self::APPLY."' is not registered. "
                .'Run the Goodwill permission seeder. No approvers can be offered until it exists.');

            return collect();
        } catch (Throwable $e) {
            Log::error('Goodwill approver lookup failed: '.$e->getMessage());

            return collect();
        }
    }

    private static function holds(?Authenticatable $user, string $permission): bool
    {
        if ($user === null || ! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return (bool) $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            // The seeder has not run. A deployment fault, not a user fault —
            // loud in the log, denied at the screen.
            Log::error("Goodwill permission '{$permission}' is not registered. "
                .'Run the Goodwill permission seeder. All Goodwill operations are refused until it exists.');

            return false;
        } catch (Throwable $e) {
            Log::error("Goodwill permission check for '{$permission}' failed: ".$e->getMessage());

            return false;
        }
    }
}

<?php

namespace App\Services\GiftCards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Throwable;

/**
 * The only authority check gift cards are permitted to use.
 *
 * ── WHY NOT `can()`, `@can`, OR THE `permission:` MIDDLEWARE ──────────────
 *
 * `AppServiceProvider::gatesRegistration()` registers
 * `Gate::before(fn () => true)`. Every ability check routed through the Gate
 * therefore returns true for any signed-in user, application-wide — including
 * Spatie's own `PermissionMiddleware`, which calls `canAny()`. A route guarded
 * by `permission:gift-card.grant` refuses nobody.
 *
 * `hasPermissionTo()` comes from Spatie's `HasPermissions` trait and reads the
 * role/permission tables directly, bypassing the Gate entirely. It is the one
 * check that still means something here.
 *
 * ── WHY THIS MATTERS MORE HERE THAN ALMOST ANYWHERE ───────────────────────
 *
 * `GiftCardService::grant()` creates spendable value from nothing. Without an
 * enforced permission it is an unlimited cash-issuing endpoint. `adjustIncrease()`
 * is the same power in a different shape. These are not "sensitive screens";
 * they are the ability to manufacture money, and they are separated from
 * SELLING a card deliberately — taking $500 and issuing $500 is a clerk's job;
 * issuing $500 that nobody paid for is not.
 *
 * ── A MISSING PERMISSION IS A DENIAL, NOT A CRASH ─────────────────────────
 *
 * Spatie raises `PermissionDoesNotExist` when a permission has never been
 * registered. Unhandled, an unseeded database turns every authority check into
 * a 500. It is caught, logged as the deployment fault it is, and treated as
 * "no" — because treating an unanswerable question as consent, on the endpoint
 * that issues money, is the failure that matters.
 */
final class GiftCardPermissions
{
    public const VIEW = 'gift-card.view';
    public const SELL = 'gift-card.sell';
    public const GRANT = 'gift-card.grant';
    public const REDEEM = 'gift-card.redeem';
    public const SUSPEND = 'gift-card.suspend';
    public const CANCEL = 'gift-card.cancel';
    public const REPLACE = 'gift-card.replace';
    public const ADJUST = 'gift-card.adjust';
    public const REPORTS = 'gift-card.reports';

    /** Every permission this feature depends on — the seeder reads this list. */
    public static function all(): array
    {
        return [
            self::VIEW => 'View Gift Cards',
            self::SELL => 'Sell Gift Cards',
            self::GRANT => 'Grant Gift Cards',
            self::REDEEM => 'Redeem Gift Cards',
            self::SUSPEND => 'Suspend Gift Cards',
            self::CANCEL => 'Cancel Gift Cards',
            self::REPLACE => 'Replace Gift Cards',
            self::ADJUST => 'Manual Balance Adjustment',
            self::REPORTS => 'View Gift Card Reports',
        ];
    }

    /**
     * Operations that CREATE OR DESTROY value and must therefore never be
     * silent: each requires an authenticated user, a reason, and an audit
     * entry. Read by the service, so the list and the enforcement cannot drift.
     */
    public static function valueChanging(): array
    {
        return [self::GRANT, self::ADJUST, self::CANCEL, self::REPLACE];
    }

    public static function canView(?Authenticatable $user): bool
    {
        return self::holds($user, self::VIEW);
    }

    public static function canSell(?Authenticatable $user): bool
    {
        return self::holds($user, self::SELL);
    }

    public static function canGrant(?Authenticatable $user): bool
    {
        return self::holds($user, self::GRANT);
    }

    public static function canRedeem(?Authenticatable $user): bool
    {
        return self::holds($user, self::REDEEM);
    }

    public static function canSuspend(?Authenticatable $user): bool
    {
        return self::holds($user, self::SUSPEND);
    }

    public static function canCancel(?Authenticatable $user): bool
    {
        return self::holds($user, self::CANCEL);
    }

    public static function canReplace(?Authenticatable $user): bool
    {
        return self::holds($user, self::REPLACE);
    }

    public static function canAdjust(?Authenticatable $user): bool
    {
        return self::holds($user, self::ADJUST);
    }

    public static function canViewReports(?Authenticatable $user): bool
    {
        return self::holds($user, self::REPORTS);
    }

    /**
     * Refunding gift-card value to a DIFFERENT destination — cash, card —
     * converts stored value into money. It is deliberately gated behind
     * ADJUST (manufacture/destroy value), not REDEEM (spend it), because that
     * is what it actually is.
     */
    public static function canRefundOffCard(?Authenticatable $user): bool
    {
        return self::holds($user, self::ADJUST);
    }

    /** @throws GiftCardException */
    public static function assert(?Authenticatable $user, string $permission): void
    {
        if (self::holds($user, $permission)) {
            return;
        }

        $label = self::all()[$permission] ?? $permission;

        throw GiftCardException::withDetail(
            GiftCardFailure::NotAuthorized,
            "Required: {$label}."
        );
    }

    private static function holds(?Authenticatable $user, string $permission): bool
    {
        if ($user === null || ! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return (bool) $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            Log::error("Gift card permission '{$permission}' is not registered. "
                .'Run GiftCardPermissionSeeder. All gift card operations are refused until it exists.');

            return false;
        } catch (Throwable $e) {
            Log::error("Gift card permission check for '{$permission}' failed: ".$e->getMessage());

            return false;
        }
    }
}

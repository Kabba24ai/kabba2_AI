<?php

namespace App\Services\Billing;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;

/**
 * Canonical resolver for the single Primary Billing Admin designation.
 *
 * The designation is stored as one settings row (Billing Settings /
 * primary_billing_admin_id) whose value is a users.id — the same
 * FK-in-a-setting pattern the app already uses for site_logo (a Media id).
 * This resolver is the ONE place future integrations (CRM collection tasks,
 * AI workflows) should call:
 *
 *     $user = app(PrimaryBillingAdminResolver::class)->primary();
 *
 * primary() re-filters through User::active(), so a since-deactivated
 * designee safely resolves to null rather than returning a stale employee.
 */
class PrimaryBillingAdminResolver
{
    public const SETTING_TYPE = 'Billing Settings';
    public const SETTING_NAME = 'primary_billing_admin_id';

    /**
     * The designated Primary Billing Admin as an active User, or null when
     * unset or no longer active.
     */
    public function primary(): ?User
    {
        $id = static::designatedId();

        if (! $id) {
            return null;
        }

        return User::where('id', $id)->active()->first();
    }

    /**
     * The raw stored users.id (may reference a now-inactive user), or null
     * when the designation is unset. Read straight from the settings row so
     * it never serves a stale cached value after a save.
     */
    public static function designatedId(): ?int
    {
        $value = Setting::where('setting_type', self::SETTING_TYPE)
            ->where('setting_name', self::SETTING_NAME)
            ->value('setting_value');

        return is_numeric($value) ? (int) $value : null;
    }
}

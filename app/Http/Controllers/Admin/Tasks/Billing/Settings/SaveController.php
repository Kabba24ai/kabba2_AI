<?php

namespace App\Http\Controllers\Admin\Tasks\Billing\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tasks\Billing\PrimaryBillingAdminRequest;
use App\Models\Configurations\Setting;
use App\Services\Billing\PrimaryBillingAdminResolver;

/**
 * Persists the Primary Billing Admin designation to the single canonical
 * settings row. Validation (active-employee guard) happens in the
 * FormRequest; an empty selection clears the designation.
 */
class SaveController extends Controller
{
    public function __invoke(PrimaryBillingAdminRequest $request)
    {
        $id = $request->input('primary_billing_admin_id') ?: null;

        $setting = Setting::firstOrNew([
            'setting_type' => PrimaryBillingAdminResolver::SETTING_TYPE,
            'setting_name' => PrimaryBillingAdminResolver::SETTING_NAME,
        ]);
        $setting->setting_title = 'Primary Billing Admin';
        $setting->value_type    = 'text';
        $setting->setting_value = $id;
        $setting->save();

        return redirect()
            ->route('admin.tasks.billing.settings.index')
            ->with('success', $id
                ? 'Primary Billing Admin updated.'
                : 'Primary Billing Admin cleared.');
    }
}

<?php

namespace App\Http\Controllers\Admin\Tasks\Billing\Settings;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Services\Billing\PrimaryBillingAdminResolver;

/**
 * Task Manager → Billing Operations → Settings.
 *
 * Presents the Primary Billing Admin designation. The employee dropdown is
 * built from active employees only (the same canonical selector Task Manager
 * uses everywhere), so an inactive employee cannot be chosen from the UI; the
 * FormRequest is the server-side guard.
 */
class IndexController extends Controller
{
    public function __invoke(PrimaryBillingAdminResolver $resolver)
    {
        $employees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $currentId = PrimaryBillingAdminResolver::designatedId();
        $current   = $resolver->primary();

        return view('admin.tasks.billing.settings.index', compact('employees', 'currentId', 'current'));
    }
}

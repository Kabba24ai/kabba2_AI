<?php

namespace App\Http\Controllers\Admin\Tasks\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingOperationsSummary;
use App\Services\Billing\PrimaryBillingAdminResolver;
use Illuminate\Support\Facades\Auth;

/**
 * Task Manager → Billing Operations → Overview.
 *
 * The Billing Admin's entry point: "what billing work needs attention now?".
 * Read-only summary cards, each linking to its workspace. Counts come from
 * the shared BillingOperationsSummary — the SAME canonical datasets the Fuel
 * and Damage workspaces consume — so nothing here can drift from them. This
 * is an entry point, NOT a processing screen: no queue, no resolution
 * controls, no customer/CRM data.
 */
class OverviewController extends Controller
{
    public function __invoke(BillingOperationsSummary $summary, PrimaryBillingAdminResolver $adminResolver)
    {
        $metrics = $summary->metrics();

        // Primary Billing Admin states: active designee / configured-but-
        // inactive / unset — each handled without blocking the page.
        $billingAdmin    = $adminResolver->primary();            // active User or null
        $designatedId    = PrimaryBillingAdminResolver::designatedId();
        $adminIsUnset    = $designatedId === null;
        $adminIsInactive = $designatedId !== null && $billingAdmin === null;
        $viewerIsAdmin   = $billingAdmin !== null && (int) Auth::id() === (int) $billingAdmin->id;

        return view('admin.tasks.billing.overview.index', compact(
            'metrics',
            'billingAdmin',
            'adminIsUnset',
            'adminIsInactive',
            'viewerIsAdmin'
        ));
    }
}

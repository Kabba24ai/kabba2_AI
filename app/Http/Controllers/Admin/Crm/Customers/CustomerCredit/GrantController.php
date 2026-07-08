<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerCredit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\CustomerCredit\GrantStoreRequest;
use App\Models\Iam\Personnel\User;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.1 — Customer Credit Administration.
 *
 * Internal-only. Grants Financial Store Credit via CustomerCreditService —
 * this controller contains no balance arithmetic or persistence of its own;
 * it validates the request, resolves the acting user, and delegates
 * entirely to the service, exactly mirroring how CustomerAccount's
 * Payment/Refund/Discount/Charge StoreControllers delegate to
 * CustomHelper today.
 *
 * Access is gated by the `permission:customer_credit.grant` route
 * middleware (see routes/admin/crm/customers/customer_credit/routes.php) —
 * not by any check in this controller — per this phase's audit finding
 * that Spatie's permission middleware is registered but had no real caller
 * anywhere in this codebase; this is the first one.
 */
class GrantController extends Controller
{
    public function __invoke(GrantStoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $user = User::findOrFail($validated['responsible_person']);

            CustomerCreditService::createFinancialCredit(
                customerId: (int) $validated['customer_id'],
                amount: (float) $validated['amount'],
                reason: $validated['reason'],
                responsibleUserId: $user->id,
                effectiveDate: $validated['effective_date'] ?? null,
                internalComments: $validated['internal_comments'] ?? null,
            );

            flash('Credit granted successfully.')->success();
            session(['active_tab' => 'store_credit']);

            return redirect()->back();
        } catch (\InvalidArgumentException $e) {
            flash($e->getMessage())->error();
            session(['active_tab' => 'store_credit']);

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            report($e);
            Log::error('Customer credit grant error: '.$e->getMessage());

            flash('Something went wrong while granting credit.')->error();
            session(['active_tab' => 'store_credit']);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while granting credit.',
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerCredit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\CustomerCredit\RedeemStoreRequest;
use App\Models\Iam\Personnel\User;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.1 — Customer Credit Administration.
 *
 * Internal, manual redemption only — no checkout or Order Entry
 * integration exists yet (explicitly out of scope for this phase). Access
 * is gated by the `permission:customer_credit.redeem` route middleware.
 */
class RedeemController extends Controller
{
    public function __invoke(RedeemStoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $user = User::findOrFail($validated['responsible_person']);

            CustomerCreditService::redeem(
                customerId: (int) $validated['customer_id'],
                amount: (float) $validated['amount'],
                reason: $validated['reason'],
                responsibleUserId: $user->id,
            );

            flash('Credit redeemed successfully.')->success();
            session(['active_tab' => 'store_credit']);

            return redirect()->back();
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            // \RuntimeException here means CustomerCreditService::redeem()
            // rejected the request because it would exceed the customer's
            // available balance ("no negative balances", per this phase's
            // mission) — surfaced to the employee as a clear message, not
            // a generic error.
            flash($e->getMessage())->error();
            session(['active_tab' => 'store_credit']);

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            report($e);
            Log::error('Customer credit redemption error: '.$e->getMessage());

            flash('Something went wrong while redeeming credit.')->error();
            session(['active_tab' => 'store_credit']);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while redeeming credit.',
            ]);
        }
    }
}

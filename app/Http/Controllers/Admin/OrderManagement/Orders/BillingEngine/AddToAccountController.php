<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Services\Billing\AddChargeToAccountService;
use Illuminate\Http\Request;

/**
 * Add Charge to Account — transfers one eligible unpaid billing charge
 * (fuel / damage / extension) to the customer's credit account (A-R).
 *
 * All accounting, eligibility re-checks, idempotency and concurrency
 * protection live in AddChargeToAccountService::transfer(), which runs in a
 * single locked transaction. This controller only validates the request and
 * shapes the JSON response — the same thin pattern as the sibling
 * billing-charges.* controllers (Resolve, Uncollectible).
 *
 * Authorization: permissions are globally bypassed in this app
 * (AppServiceProvider Gate::before), so server-side re-validation of charge
 * state + account eligibility in the service — never the hidden button — is
 * the real guard. `performed_by` records who did it (operational attribution),
 * matching the resolve/uncollectible endpoints.
 */
class AddToAccountController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $validated = $request->validate([
            'performed_by' => ['required', 'integer', 'exists:users,id'],
            'note'         => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = AddChargeToAccountService::transfer(
                $chargeUniqueId,
                (int) $validated['performed_by'],
                $validated['note'] ?? null,
            );
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['status'] === 'already'
                ? "This charge is already on the customer's account."
                : "Charge added to the customer's account.",
        ]);
    }
}

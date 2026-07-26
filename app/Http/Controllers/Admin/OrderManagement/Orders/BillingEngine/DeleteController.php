<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\DeleteExtensionTransactionRequest;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use App\Services\ExtensionTransactionService;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $charge = BillingCharge::withTrashed()->where('unique_id', $chargeUniqueId)->firstOrFail();

        if (!$charge->billing_charge_type?->createsChildOrder()) {
            return response()->json(['success' => false, 'message' => 'Only extension charges can be deleted.'], 422);
        }

        // withTrashed: the child may already be gone — the coordinated
        // operation still removes the surviving charge.
        $child = $charge->child_order_id
            ? Order::withTrashed()->find($charge->child_order_id)
            : null;

        // Consistency Initiative Phase 10: EVERY extension delete now requires
        // an administrative disposition (verified employee + PIN + reason) —
        // no longer only paid-with-no-refund/void. Resolving the FormRequest
        // runs its validation (422 on missing/invalid processed_by /
        // employee_code / reason) before anything is deleted. The payment
        // state is still recorded in history for audit, it just no longer
        // gates whether authorization is required.
        $disposition = ExtensionTransactionService::buildDisposition(
            app(DeleteExtensionTransactionRequest::class)->validated()
        );

        $result = ExtensionTransactionService::delete(
            $child,
            $charge,
            ExtensionTransactionService::ENTRY_BILLING_ROW,
            $request->user(),
            $disposition,
        );

        // Explicit confirmation identifying exactly what was deleted.
        $identity = trim(
            ($child?->order_number ? "extension #{$child->order_number}" : "charge {$charge->unique_id}")
            . ' — $' . number_format((float) $charge->amount + (float) $charge->tax_amount, 2)
        );

        return response()->json([
            'success' => true,
            'message' => $result['already_deleted']
                ? "This extension transaction ({$identity}) was already deleted."
                : "Deleted permanently: {$identity}, including its child order.",
        ]);
    }
}

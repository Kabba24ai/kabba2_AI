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

        // Paid extension with no Kabba refund/void: never silently delete —
        // require the administrative disposition (verified employee + reason).
        $disposition = null;
        if (ExtensionTransactionService::requiresDisposition($charge, $child)) {
            if (!$request->filled('processed_by')) {
                return response()->json([
                    'success'              => false,
                    'requires_disposition' => true,
                    'message'              => 'This extension is paid with no recorded refund or void. Deleting it requires an administrative disposition (verified employee and reason).',
                ], 422);
            }

            // Resolving the FormRequest runs its validation (422 on failure)
            $disposition = ExtensionTransactionService::buildDisposition(
                app(DeleteExtensionTransactionRequest::class)->validated()
            );
        }

        $result = ExtensionTransactionService::delete(
            $child,
            $charge,
            ExtensionTransactionService::ENTRY_BILLING_ROW,
            $request->user(),
            $disposition,
        );

        return response()->json([
            'success' => true,
            'message' => $result['already_deleted']
                ? 'This extension transaction was already deleted.'
                : 'Extension charge and its child order were deleted together.',
        ]);
    }
}

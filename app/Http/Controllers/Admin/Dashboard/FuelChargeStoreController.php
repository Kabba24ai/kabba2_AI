<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\ChargeService;
use Illuminate\Http\Request;

/**
 * Shared New Fuel Charge modal — the ONE endpoint all three entry points
 * post to (Dashboard card, Fuel Workspace, CRM customer page), with the
 * identical normalized payload. Creation itself is
 * ChargeService::createManualCharge() — this controller only validates
 * and enforces the order/customer integrity rule.
 */
class FuelChargeStoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            // Optional order link: when present, the CUSTOMER IS DERIVED
            // FROM THE ORDER — a submitted customer_id must match it, so an
            // employee can never attach order A's charge to customer B.
            'order_id'           => ['nullable', 'integer', 'exists:orders,id'],
            'customer_id'        => ['required_without:order_id', 'nullable', 'exists:customers,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
            'sales_tax_type'     => ['nullable', 'in:add,free,reverse'],
            'source_context'     => ['nullable', 'in:dashboard,fuel_workspace,crm,order_details'],
            // Optional pre-tax Store Credit discount applied at creation time.
            'store_credit_discount' => ['nullable', 'numeric', 'min:0.01'],
            'store_credit_key'      => ['nullable', 'string', 'max:64'],
        ]);

        $orderId = $validated['order_id'] ?? null;
        $customerId = $validated['customer_id'] ?? null;

        if ($orderId !== null) {
            $order = Order::findOrFail($orderId);

            if ($order->customer_id === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'That order has no customer on record — select the customer directly instead.',
                ], 422);
            }

            if ($customerId !== null && (int) $customerId !== (int) $order->customer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected customer does not match the selected order.',
                ], 422);
            }

            $customerId = (int) $order->customer_id;
        }

        try {
            $record = ChargeService::createManualCharge(
                customerId: (int) $customerId,
                type: 'fuel',
                amount: (float) $validated['amount'],
                salesTaxType: $validated['sales_tax_type'] ?? null,
                notes: $validated['notes'] ?? null,
                responsibleUserId: (int) $validated['responsible_person'],
                orderId: $orderId,
                sourceContext: $validated['source_context'] ?? 'dashboard',
                storeCreditDiscount: isset($validated['store_credit_discount']) ? (float) $validated['store_credit_discount'] : null,
                discountIdempotencyKey: isset($validated['store_credit_discount'])
                    ? ($validated['store_credit_key'] ?? ('fuel_scd:' . (string) \Illuminate\Support\Str::uuid()))
                    : null,
            );
        } catch (\App\Services\Discounts\DiscountException $e) {
            // Store Credit discount rejected — the whole creation rolled back.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }

        // The Billing Engine bridge row created inside createManualCharge()
        // — the canonical payment reference: submitting its unique_id with
        // the payment turns on the exact-total validation and the
        // BillingEngine::markPaid sync in PaymentStoreController.
        $bridge = \App\Models\Orders\BillingCharge::where('customer_account_id', $record->id)
            ->where('billing_charge_type', 'fuel')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Fuel Charge created successfully.',
            // The Billing Engine post-charge payment handoff contract:
            // everything the canonical payment workflow needs, supplied by
            // the SHARED response — never assembled by individual launchers.
            'charge' => [
                'type'                       => 'fuel',
                'customer_account_unique_id' => $record->unique_id,
                'billing_charge_unique_id'   => $bridge?->unique_id,
                'customer_id'                => (int) $record->customer_id,
                'order_id'                   => $record->order_id,
                'amount'                     => (float) $record->amount,
                'sales_tax_type'             => $record->sales_tax_type,
                // Tax-inclusive collectible total — updateCreditBalance()
                // resolved sales_tax to the actual rate during creation.
                // PaymentStoreController enforces this exact figure when the
                // billing_charge_unique_id accompanies the payment.
                'amount_total'               => \App\Services\ChargeTaxCalculator::calculate(
                    (float) $record->amount, $record->sales_tax_type, (float) $record->sales_tax
                )['total_amount'],
                // Saved payment profiles from the shared flow — identical
                // regardless of which surface launched the modal.
                'customer_cards'             => $record->customer?->cards
                    ?->map(fn ($c) => ['id' => $c->unique_id, 'label' => $c->card_number])
                    ->values() ?? [],
            ],
        ]);
    }
}

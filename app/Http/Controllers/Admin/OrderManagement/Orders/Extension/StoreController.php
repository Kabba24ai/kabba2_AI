<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Extension;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Http\DataObjects\BillingChargeRequest;
use App\Http\Requests\Admin\OrderManagement\Orders\Extension\StoreRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\BillingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    public function __invoke(string $uniqueId, StoreRequest $request)
    {
        $validated = $request->validated();

        $order = Order::where('unique_id', $uniqueId)->with('billingAddress')->firstOrFail();
        $user  = User::findOrFail($validated['responsible_person']);

        // Duplicate-click / retry guard: the modal generates a UUID per open;
        // a second submit carrying the same key must not create a second
        // child order (the BillingEngine idempotency key can't help — each
        // duplicate order would mint a fresh extension id).
        if (!empty($validated['request_uuid'])
            && !\Illuminate\Support\Facades\Cache::add('extension-create:' . $validated['request_uuid'], 1, 300)) {
            return response()->json([
                'success'   => false,
                'duplicate' => true,
                'message'   => 'This Order Enhancement was already submitted.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Generate suffix: A for first extension, B for second, etc.
            // withTrashed() prevents the soft-delete collision bug: soft-deleted extensions
            // are excluded from a plain count but still hold the UNIQUE order_number slot,
            // causing a fatal duplicate-key error on the next creation attempt.
            // The order_number LIKE filter excludes reorders (which share reference_order_number
            // but receive a new sequential number, not a suffixed one).
            $existingCount = Order::withTrashed()
                ->where('reference_order_number', $order->order_number)
                ->where('order_number', 'like', $order->order_number . '-%')
                ->count();
            if ($existingCount >= 26) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Maximum of 26 Order Enhancements per order reached.'], 422);
            }
            $suffix = chr(65 + $existingCount); // A, B, C…
            $extensionOrderNumber = $order->order_number . '-' . $suffix;

            // Calculate amounts
            $baseAmount = round((float) $validated['base_amount'], 2);
            $salesTaxRate = (float) (ConfigurationHelper::getSettings(null, 'sales_tax') ?? 0);

            // Optional PRE-TAX Store Credit discount, applied AT creation time
            // (extensions have no post-creation pre-posting window). Reduce the
            // base BEFORE tax so the child order, pending placeholder and Billing
            // Engine bridge all inherit the discounted values — inside this same
            // transaction, so the redemption and the extension roll back together.
            $scDiscountPending = null;
            $scRequested = round((float) ($validated['store_credit_discount'] ?? 0), 2);
            if ($scRequested > 0) {
                $discountSvc = app(\App\Services\Discounts\DiscountApplicationService::class);
                $scKey = 'ext_scd:' . ($validated['request_uuid'] ?? (string) \Illuminate\Support\Str::uuid());
                if ($discountSvc->existingCreationDiscount($scKey)) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'duplicate' => true, 'message' => 'This Store Credit discount was already applied.'], 409);
                }
                $cd = $discountSvc->computeAndRedeemForCreation(
                    (int) $order->customer_id,
                    $baseAmount,
                    $scRequested,
                    $validated['add_tax'] ? $salesTaxRate : 0.0,
                    $scKey,
                    $user->id,
                    'Store Credit discount — extension',
                );
                $baseAmount = $cd['result']->discountedProductValue;   // reduced base
                $taxAmount  = $cd['result']->taxAfter;                 // tax on discounted base (canonical)
                $scDiscountPending = ['key' => $scKey, 'cd' => $cd, 'requested' => $scRequested];
            } else {
                $taxAmount = $validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00;
            }
            $grandTotal   = $baseAmount + $taxAmount;

            // Create extension order with pre-set order_number (boot() guard preserves it)
            $extension = Order::create([
                'order_number'           => $extensionOrderNumber,
                'reference_order_number' => $order->order_number,
                'customer_id'            => $order->customer_id,
                'customer_name'          => $order->customer_name,
                'customer_email'         => $order->customer_email,
                'customer_phone'         => $order->customer_phone,
                'company_name'           => $order->company_name,
                'subtotal'               => $baseAmount,
                'tax_amount'             => $taxAmount,
                'grand_total'            => $grandTotal,
                'is_tax_exempt'          => $validated['add_tax'] ? 'No' : 'Yes',
                'order_note'             => $validated['description'],
            ]);

            // Link the Store Credit discount to the created extension (same txn).
            if ($scDiscountPending !== null) {
                app(\App\Services\Discounts\DiscountApplicationService::class)->recordCreationDiscount(
                    $scDiscountPending['cd']['result'],
                    $scDiscountPending['cd']['redemption_id'],
                    \App\Enums\Discounts\DiscountTargetType::Extension,
                    (int) $extension->id,
                    (int) $order->customer_id,
                    $scDiscountPending['key'],
                    $user->id,
                    $scDiscountPending['requested'],
                    'Store Credit discount — extension',
                    'admin_extension',
                );
            }

            // Store notes as a separate OrderNote on the extension order
            if (!empty($validated['notes'])) {
                $extension->notes()->create([
                    'note'            => $validated['notes'],
                    'user_id'         => $user->id,
                    'created_by_type' => User::class,
                    'created_by_id'   => $user->id,
                ]);
            }

            // Copy billing address from original order so it appears in the Orders index table
            if ($order->billingAddress) {
                $extension->addresses()->create([
                    'type'       => 'Billing',
                    'first_name' => $order->billingAddress->first_name,
                    'last_name'  => $order->billingAddress->last_name,
                    'email'      => $order->billingAddress->email,
                    'phone'      => $order->billingAddress->phone,
                    'address'    => $order->billingAddress->address,
                    'city'       => $order->billingAddress->city,
                    'state'      => $order->billingAddress->state,
                    'state_id'   => $order->billingAddress->state_id,
                    'zip_code'   => $order->billingAddress->zip_code,
                ]);
            }

            // Pending payment placeholder so the order shows as "Pending" until paid
            $extension->payments()->create([
                'payment_datetime'  => now(),
                'payment_method'    => OrderPaymentMethod::COD->value,
                'amount'            => $grandTotal,
                'status'            => OrderPaymentStatus::Pending->value,
                'created_by_id'     => $user->id,
                'created_by_type'   => User::class,
            ]);

            // Write history entry on the ORIGINAL order
            $taxNote = $taxAmount > 0 ? ' | Tax: $' . number_format($taxAmount, 2) : ' | No Tax';
            $order->history()->create([
                'customer_id' => $order->customer_id,
                'user_id'     => $user->id,
                'action_by'   => OrderHistoryActionBy::User,
                'action_date' => now(),
                'action'      => OrderHistoryAction::ExtensionChargeCreated,
                'description' => "Order Enhancement {$extensionOrderNumber} created — {$validated['description']} | \${$baseAmount}{$taxNote} | Total: \$" . number_format($grandTotal, 2),
            ]);

            DB::commit();

            // ── Billing Engine bridge (Phase 5B) ───────────────────────────
            $billingCharge = null;
            try {
                // Derive the store from the parent order's first product so billing
                // charges can participate in store-filtered reports without a join.
                $storeId = \DB::table('order_products')
                    ->where('order_id', $order->id)
                    ->whereNotNull('delivery_store_id')
                    ->whereNull('deleted_at')
                    ->value('delivery_store_id');

                $billingCharge = BillingEngine::charge(new BillingChargeRequest(
                    type:                BillingChargeType::Extension->value,
                    orderId:             $order->id,
                    customerId:          (int) $order->customer_id,
                    amount:              $baseAmount,
                    taxType:             $validated['add_tax'] ? 'add' : 'free',
                    responsiblePersonId: $user->id,
                    notes:               $validated['notes'] ?? null,
                    sourceModule:        BillingSourceModule::RentalExtension->value,
                    sourceEvent:         BillingSourceEvent::RentalExtensionCreated->value,
                    sourceReferenceType: 'Order',
                    sourceReferenceId:   $extension->id,
                    metadata: [
                        'legacy_controller'   => 'Extension\\StoreController',
                        'parent_order_id'     => $order->id,
                        'parent_order_number' => $order->order_number,
                        'child_order_id'      => $extension->id,
                        'child_order_number'  => $extension->order_number,
                        'base_amount'         => $baseAmount,
                        'tax_amount'          => $taxAmount,
                        'add_tax'             => $validated['add_tax'],
                        'description'         => $validated['description'],
                        'extension_context'   => true,
                    ],
                    idempotencyKey:    "rental_extension:{$extension->id}",
                    childOrderId:      $extension->id,
                    customerAccountId: null,  // extensions do not create a CustomerAccount row
                    taxAmount:         $taxAmount > 0 ? $taxAmount : null,
                    storeId:           $storeId ? (int) $storeId : null,
                ));
            } catch (\Throwable $e) {
                Log::channel('billing_engine')->error(
                    "BillingEngine bridge failed | controller=Extension\\StoreController " .
                    "| extension_order_id={$extension->id} | parent_order_id={$order->id} " .
                    "| error=" . $e->getMessage()
                );
            }

            return response()->json([
                'success'   => true,
                'message'   => "Order Enhancement {$extensionOrderNumber} created successfully.",
                'extension' => [
                    'unique_id'    => $extension->unique_id,
                    'order_number' => $extension->order_number,
                    'edit_url'     => route('admin.order-management.orders.edit', $extension->unique_id),
                ],
                // Payment-chaining payload: lets the intake flow open the
                // existing Make a Payment modal against this charge without
                // hunting for the Billing Engine row. Null if the bridge
                // failed — the client falls back to a plain reload.
                'billing_charge' => $billingCharge ? [
                    'unique_id'    => $billingCharge->unique_id,
                    'total'        => (float) $grandTotal,
                    'customer_id'  => (int) $order->customer_id,
                    'order_number' => $extension->order_number,
                ] : null,
            ]);
        } catch (\App\Services\Discounts\DiscountException $e) {
            // Store Credit discount rejected (over-available / over-eligible /
            // etc.) — roll back the redemption AND the extension together.
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Failed to create Order Enhancement. Please try again.'], 500);
        }
    }
}

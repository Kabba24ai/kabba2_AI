<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Extension;

use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\Extension\StoreRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(string $uniqueId, StoreRequest $request)
    {
        $validated = $request->validated();

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();
        $user  = User::findOrFail($validated['responsible_person']);

        DB::beginTransaction();

        try {
            // Generate suffix: A for first extension, B for second, etc.
            $existingCount = Order::where('reference_order_number', $order->order_number)->count();
            if ($existingCount >= 26) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Maximum of 26 extensions per order reached.'], 422);
            }
            $suffix = chr(65 + $existingCount); // A, B, C…
            $extensionOrderNumber = $order->order_number . '-' . $suffix;

            // Calculate amounts
            $baseAmount = round((float) $validated['base_amount'], 2);
            $salesTaxRate = (float) (ConfigurationHelper::getSettings(null, 'sales_tax') ?? 0);
            $taxAmount    = $validated['add_tax'] ? round($baseAmount * $salesTaxRate, 2) : 0.00;
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
                'order_note'             => $validated['description'] . ($validated['notes'] ? "\n" . $validated['notes'] : ''),
            ]);

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
                'description' => "Extension charge {$extensionOrderNumber} created — {$validated['description']} | \${$baseAmount}{$taxNote} | Total: \$" . number_format($grandTotal, 2),
            ]);

            DB::commit();

            return response()->json([
                'success'   => true,
                'message'   => "Extension charge {$extensionOrderNumber} created successfully.",
                'extension' => [
                    'unique_id'    => $extension->unique_id,
                    'order_number' => $extension->order_number,
                    'edit_url'     => route('admin.order-management.orders.edit', $extension->unique_id),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Failed to create extension charge. Please try again.'], 500);
        }
    }
}

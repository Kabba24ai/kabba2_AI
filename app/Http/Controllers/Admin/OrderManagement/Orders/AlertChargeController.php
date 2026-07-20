<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\Controllers\Controller;
use App\Http\DataObjects\BillingChargeRequest;
use App\Helpers\CustomHelper;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\BillingEngine;
use App\Services\ChargeTaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertChargeController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $request->validate([
            // Fuel creation moved to the shared New Fuel Charge modal
            // (FuelChargeStoreController -> ChargeService::createManualCharge)
            // — the fuel branch here was unreachable dead code and has been
            // retired (Billing Charge Operations Commonization). This
            // controller now serves the legacy Damage Alert modal only,
            // until Phase 1B moves damage onto the shared path too.
            'type'               => ['required', 'in:damage'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
            'sales_tax_type'     => ['nullable', 'in:add,free,reverse'],
        ]);

        $order = Order::where('unique_id', $uniqueId)->with('customer')->firstOrFail();
        $user  = User::findOrFail($request->responsible_person);

        DB::beginTransaction();

        try {
            $record = new CustomerAccount();
            $record->customer_id             = $order->customer_id;
            $record->order_id                = $order->id;
            $record->amount                  = $request->amount;
            $record->reason                  = 'Damages';
            $record->responsible_person_id   = $user->id;
            $record->responsible_person_name = $user->full_name;
            $record->notes                   = $request->notes;
            $record->date                    = now();
            $record->sales_tax_type          = $request->sales_tax_type ?? 'free';
            $record->sales_tax               = 0;
            $record->type                    = 'charge';
            $record->fuel_alert_status       = null;
            $record->damage_alert_status     = 'pending';
            $record->save();

            CustomHelper::updateCreditBalance($record);

            $chargeType = 'Damage charge';

            $description = "{$chargeType} added.";
            $description .= " Amount: $" . number_format($record->amount, 2) . ".";

            if ($record->responsible_person_name) {
                $description .= " Responsible person: {$record->responsible_person_name}.";
            }

            if ($request->filled('notes')) {
                $description .= " Notes: {$request->notes}";
            }

            if ($record->customer) {
                $record->customer->notes()->create([
                    'customer_account_id' => $record->id,
                    'description'         => $description,
                    'created_by'          => auth()->id(),
                ]);
            }

            DB::commit();

            // ── Resolve billing amounts from the settled CustomerAccount record ──
            // updateCreditBalance() sets $record->sales_tax to the actual rate (e.g. 0.0975).
            // Sales Tax Architecture Correction: now sourced from the canonical
            // ChargeTaxCalculator instead of a copy of the same formula inline here.
            $resolved = ChargeTaxCalculator::calculate((float) $record->amount, $record->sales_tax_type, (float) $record->sales_tax);
            $billingBaseAmount = $resolved['base_amount'];
            $billingTaxAmount  = $resolved['tax_amount'];

            // ── Billing Engine bridge (damage only) ───────────────────────
            if ($request->type === 'damage') {
                // Phase 4C
                try {
                    BillingEngine::charge(new BillingChargeRequest(
                        type:                BillingChargeType::Damage->value,
                        orderId:             $order->id,
                        customerId:          (int) $order->customer_id,
                        amount:              $billingBaseAmount,
                        taxType:             $record->sales_tax_type,
                        responsiblePersonId: $user->id,
                        notes:               $record->notes,
                        sourceModule:        BillingSourceModule::AdminDamageCharge->value,
                        sourceEvent:         BillingSourceEvent::AdminDamageChargeCreated->value,
                        sourceReferenceType: 'CustomerAccount',
                        sourceReferenceId:   $record->id,
                        metadata: [
                            'legacy_controller'          => 'AlertChargeController',
                            'legacy_customer_account_id' => $record->id,
                            'order_id'                   => $order->id,
                            'order_unique_id'            => $uniqueId,
                            'customer_id'                => $order->customer_id,
                            'sales_tax_type'             => $record->sales_tax_type,
                            'alert_context'              => true,
                        ],
                        idempotencyKey:    "admin_damage_alert_charge:{$record->id}",
                        customerAccountId: $record->id,
                        taxAmount:         $billingTaxAmount,
                    ));
                } catch (\Throwable $e) {
                    Log::channel('billing_engine')->error(
                        "BillingEngine bridge failed | controller=AlertChargeController " .
                        "| customer_account_id={$record->id} | order_id={$order->id} " .
                        "| error=" . $e->getMessage()
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Damage Alert created successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}

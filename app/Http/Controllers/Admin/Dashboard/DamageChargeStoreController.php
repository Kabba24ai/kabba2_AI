<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\Controllers\Controller;
use App\Http\DataObjects\BillingChargeRequest;
use App\Helpers\CustomHelper;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Services\BillingEngine;
use App\Services\ChargeTaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DamageChargeStoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'customer_id'        => ['required', 'exists:customers,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
            'sales_tax_type'     => ['nullable', 'in:add,free,reverse'],
            // Optional pre-tax Store Credit discount applied at creation time.
            'store_credit_discount' => ['nullable', 'numeric', 'min:0.01'],
            'store_credit_key'      => ['nullable', 'string', 'max:64'],
        ]);

        $user = User::findOrFail($request->responsible_person);

        $scRequested = round((float) ($request->store_credit_discount ?? 0), 2);
        $applyDiscount = $scRequested > 0;
        $scKey = $request->store_credit_key ?: ('damage_scd:' . (string) \Illuminate\Support\Str::uuid());
        $discountSvc = app(\App\Services\Discounts\DiscountApplicationService::class);

        // Idempotent retry: the discounted charge already exists → return it.
        if ($applyDiscount && ($existingDiscount = $discountSvc->existingCreationDiscount($scKey))) {
            return response()->json(['success' => true, 'message' => 'Damage Alert already created.'], 200);
        }

        DB::beginTransaction();

        try {
            $effectiveAmount = round((float) $request->amount, 2);
            $effectiveTreatment = $request->sales_tax_type ?? 'free';
            $pendingDiscount = null;

            if ($applyDiscount) {
                // PRE-TAX discount BEFORE the A/R booking, in this same txn.
                $pendingDiscount = $discountSvc->computeChargeDiscount(
                    (int) $request->customer_id, (float) $request->amount, $request->sales_tax_type,
                    $scRequested, $scKey, $user->id, 'damage',
                );
                $effectiveAmount = $pendingDiscount['effective_amount'];
                $effectiveTreatment = $pendingDiscount['effective_treatment'];
            }

            $record                          = new CustomerAccount();
            $record->customer_id             = $request->customer_id;
            $record->amount                  = $effectiveAmount;
            $record->reason                  = 'Damages';
            $record->responsible_person_id   = $user->id;
            $record->responsible_person_name = $user->full_name;
            $record->notes                   = $request->notes;
            $record->date                    = now();
            $record->sales_tax_type          = $effectiveTreatment;
            $record->sales_tax               = 0;
            $record->type                    = 'charge';
            $record->damage_alert_status     = 'pending';
            $record->save();

            CustomHelper::updateCreditBalance($record);

            if ($pendingDiscount !== null) {
                $discountSvc->recordCreationDiscount(
                    $pendingDiscount['result'], $pendingDiscount['redemption_id'],
                    \App\Enums\Discounts\DiscountTargetType::DamageCharge, (int) $record->id,
                    (int) $request->customer_id, $scKey, $user->id, $scRequested,
                    'Store Credit discount — damage', 'admin_damage',
                );
            }


            $description = "Damage charge added.";
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

            // Sales Tax Architecture Correction: this call previously passed
            // $record->amount (the raw entered amount) with no taxAmount at
            // all — BillingCharge.tax_amount defaulted to 0 regardless of
            // the sales_tax_type selected, since updateCreditBalance()'s
            // resulting rate was never resolved into a base/tax split here
            // (unlike the sibling FuelChargeStoreController/AlertChargeController,
            // which already did this). Now sourced from the canonical
            // ChargeTaxCalculator.
            $resolved = ChargeTaxCalculator::calculate((float) $record->amount, $record->sales_tax_type, (float) $record->sales_tax);

            // ── Billing Engine bridge (Phase 4B) ───────────────────────────
            try {
                BillingEngine::charge(new BillingChargeRequest(
                    type:                BillingChargeType::Damage->value,
                    orderId:             null, // Dashboard modal: no order context
                    customerId:          (int) $record->customer_id,
                    amount:              $resolved['base_amount'],
                    taxType:             $record->sales_tax_type,
                    responsiblePersonId: $user->id,
                    notes:               $record->notes,
                    sourceModule:        BillingSourceModule::AdminDamageCharge->value,
                    sourceEvent:         BillingSourceEvent::AdminDamageChargeCreated->value,
                    sourceReferenceType: 'CustomerAccount',
                    sourceReferenceId:   $record->id,
                    metadata: [
                        'legacy_controller'          => 'DamageChargeStoreController',
                        'legacy_customer_account_id' => $record->id,
                        'customer_id'                => $record->customer_id,
                        'sales_tax_type'             => $record->sales_tax_type,
                        'dashboard_context'          => true,
                    ],
                    idempotencyKey:    "admin_dashboard_damage_charge:{$record->id}",
                    customerAccountId: $record->id,
                    taxAmount:         $resolved['tax_amount'],
                ));
            } catch (\Throwable $e) {
                Log::channel('billing_engine')->error(
                    "BillingEngine bridge failed | controller=DamageChargeStoreController " .
                    "| customer_account_id={$record->id} | customer_id={$record->customer_id} " .
                    "| error=" . $e->getMessage()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Damage Alert created successfully.',
            ]);
        } catch (\App\Services\Discounts\DiscountException $e) {
            // Store Credit discount rejected — redemption + charge roll back together.
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}

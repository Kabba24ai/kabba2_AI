<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Http\Controllers\Controller;
use App\Http\DataObjects\BillingChargeRequest;
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\ChargeStoreRequest;
use App\Models\Customers\CustomerAccount;
use App\Services\BillingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Iam\Personnel\User ;
use App\Models\Configurations\Setting;
use App\Helpers\CustomHelper;

class ChargeStoreController extends Controller
{
    public function __invoke(ChargeStoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

      

        try {
            $record = new CustomerAccount();
            $record->customer_id = $validated['customer_id'];
            $record->amount = $validated['amount'];
            $record->reason = $validated['reason'];
            // $record->responsible_person = $validated['responsible_person'];
            
            // Fetch user and store both ID and full_name
            $user = User::findOrFail($validated['responsible_person']);
            $record->responsible_person_id = $user->id ?? '';
            $record->responsible_person_name = $user->full_name ?? '';

            // Set sales tax type
            $record->sales_tax_type = $validated['sales_tax'] ?? null;

            $record->sales_tax = 0.00;
           
            $record->notes = $validated['notes'] ?? null;
            $record->date = now();
            $record->type = 'charge';
            $record->fuel_alert_status   = ($validated['reason'] === 'Fuel Charge') ? 'pending' : null;
            $record->damage_alert_status = ($validated['reason'] === 'Damages')     ? 'pending' : null;

            $record->save();

              
            CustomHelper::updateCreditBalance($record);

            DB::commit();

            // ── Billing Engine bridge ──────────────────────────────────────
            if ($validated['reason'] === 'Fuel Charge') {
                // Phase 3C
                try {
                    BillingEngine::charge(new BillingChargeRequest(
                        type:                BillingChargeType::Fuel->value,
                        orderId:             null, // CRM charge modal: no order context
                        customerId:          (int) $record->customer_id,
                        amount:              (float) $record->amount,
                        taxType:             $record->sales_tax_type ?? 'free',
                        responsiblePersonId: $user->id,
                        notes:               $record->notes,
                        sourceModule:        BillingSourceModule::AdminFuelCharge->value,
                        sourceEvent:         BillingSourceEvent::AdminFuelChargeCreated->value,
                        sourceReferenceType: 'CustomerAccount',
                        sourceReferenceId:   $record->id,
                        metadata: [
                            'legacy_controller'          => 'ChargeStoreController',
                            'legacy_customer_account_id' => $record->id,
                            'customer_id'                => $record->customer_id,
                            'sales_tax_type'             => $record->sales_tax_type,
                        ],
                        idempotencyKey:    "crm_fuel_charge:{$record->id}",
                        customerAccountId: $record->id,
                    ));
                } catch (\Throwable $e) {
                    Log::channel('billing_engine')->error(
                        "BillingEngine bridge failed | controller=ChargeStoreController " .
                        "| customer_account_id={$record->id} | customer_id={$record->customer_id} " .
                        "| error=" . $e->getMessage()
                    );
                }
            } elseif ($validated['reason'] === 'Damages') {
                // Phase 4D
                try {
                    BillingEngine::charge(new BillingChargeRequest(
                        type:                BillingChargeType::Damage->value,
                        orderId:             null, // CRM charge modal: no order context
                        customerId:          (int) $record->customer_id,
                        amount:              (float) $record->amount,
                        taxType:             $record->sales_tax_type ?? 'free',
                        responsiblePersonId: $user->id,
                        notes:               $record->notes,
                        sourceModule:        BillingSourceModule::AdminDamageCharge->value,
                        sourceEvent:         BillingSourceEvent::AdminDamageChargeCreated->value,
                        sourceReferenceType: 'CustomerAccount',
                        sourceReferenceId:   $record->id,
                        metadata: [
                            'legacy_controller'          => 'ChargeStoreController',
                            'legacy_customer_account_id' => $record->id,
                            'customer_id'                => $record->customer_id,
                            'sales_tax_type'             => $record->sales_tax_type,
                            'crm_context'                => true,
                        ],
                        idempotencyKey:    "crm_damage_charge:{$record->id}",
                        customerAccountId: $record->id,
                    ));
                } catch (\Throwable $e) {
                    Log::channel('billing_engine')->error(
                        "BillingEngine bridge failed | controller=ChargeStoreController " .
                        "| customer_account_id={$record->id} | customer_id={$record->customer_id} " .
                        "| error=" . $e->getMessage()
                    );
                }
            }

            flash('Charge successfully added')->success();
            // session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);

            return redirect()->back();

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong adding charge. Please try again.')->error();
            // session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);

            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while adding the charge. Please try again.',
            ]);
        }
    }
}

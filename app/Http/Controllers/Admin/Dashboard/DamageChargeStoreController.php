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
        ]);

        $user = User::findOrFail($request->responsible_person);

        DB::beginTransaction();

        try {
            $record                          = new CustomerAccount();
            $record->customer_id             = $request->customer_id;
            $record->amount                  = $request->amount;
            $record->reason                  = 'Damages';
            $record->responsible_person_id   = $user->id;
            $record->responsible_person_name = $user->full_name;
            $record->notes                   = $request->notes;
            $record->date                    = now();
            $record->sales_tax_type          = $request->sales_tax_type ?? 'free';
            $record->sales_tax               = 0;
            $record->type                    = 'charge';
            $record->damage_alert_status     = 'pending';
            $record->save();

            CustomHelper::updateCreditBalance($record);


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

            // ── Billing Engine bridge (Phase 4B) ───────────────────────────
            try {
                BillingEngine::charge(new BillingChargeRequest(
                    type:                BillingChargeType::Damage->value,
                    orderId:             null, // Dashboard modal: no order context
                    customerId:          (int) $record->customer_id,
                    amount:              (float) $record->amount,
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
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}

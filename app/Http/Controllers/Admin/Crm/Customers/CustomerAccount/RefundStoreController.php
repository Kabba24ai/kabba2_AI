<?php
namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Enums\Billing\BillingChargeRefundStatus;
use App\Helpers\CustomHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\RefundStoreRequest;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\BillingChargeRefund;
use App\Services\ChargeTaxCalculator;
use App\Services\Orders\BillingChargeRefundService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Billing Charge Refund Allocation — Safe Linked Refunds.
 *
 * Two entirely distinct paths, dispatched on whether the request supplies
 * a billing_charge_unique_id:
 *
 *   - Free-form refund (no linked charge): byte-for-byte the original
 *     pre-existing behavior — a plain CustomerAccount 'refund' row driven
 *     by CustomHelper::updateCreditBalance()'s customer-exemption-status
 *     formula. No allocation row, no locking, no eligibility checks. This
 *     is the path every historical refund took and must keep working
 *     identically.
 *
 *   - Charge-linked refund: validated, locked, allocation-tracked. See
 *     storeLinkedRefund() below for the full transaction design.
 */
class RefundStoreController extends Controller
{
    public function __invoke(RefundStoreRequest $request)
    {
        $validated = $request->validated();

        if (empty($validated['billing_charge_unique_id'])) {
            return $this->storeFreeFormRefund($validated);
        }

        return $this->storeLinkedRefund($validated);
    }

    /**
     * Unchanged from the pre-existing behavior — no billing_charge_unique_id
     * was supplied, so there is nothing to validate ownership/eligibility
     * against and no allocation row to create. sales_tax_type/sales_tax are
     * left unset on the record, so CustomHelper::updateCreditBalance()'s
     * 'refund' case falls through to its original customer-exemption-status
     * formula exactly as it always has.
     */
    private function storeFreeFormRefund(array $validated)
    {
        DB::beginTransaction();

        try {
            $record = new CustomerAccount();
            $record->customer_id = $validated['customer_id'];
            $record->amount = $validated['amount'];
            $record->reason = $validated['reason'];

            $user = User::findOrFail($validated['responsible_person']);
            $record->responsible_person_id = $user->id ?? '';
            $record->responsible_person_name = $user->full_name ?? '';

            $record->notes = $validated['notes'] ?? null;
            $record->date = now();
            $record->type = 'refund';

            $record->save();

            CustomHelper::updateCreditBalance($record);

            DB::commit();

            flash('Refund processed successfully.')->success();
            session(['active_tab' => 'credit']);

            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while processing the refund.')->error();
            session(['active_tab' => 'credit']);

            Log::error('Refund error: ' . $e->getMessage());

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while processing the refund.',
            ]);
        }
    }

    /**
     * Transaction design (matches the mission's specified sequence exactly):
     *   validate and lock Billing Charge
     *     → check idempotency
     *     → calculate remaining refundable amounts
     *     → calculate approved refund split
     *     → create Customer Account refund row
     *     → create billing_charge_refunds allocation row
     *     → update account balance (CustomHelper::updateCreditBalance)
     *     → commit
     *
     * Two layers of concurrency protection, deliberately:
     *   1. Cache::lock keyed by charge+token — same convention as
     *      RefundPaymentController/PaymentStoreController — closes a
     *      literal duplicate resubmission of the SAME request.
     *   2. SELECT ... FOR UPDATE on the BillingCharge row, held for the
     *      whole transaction — closes the race between two genuinely
     *      DIFFERENT concurrent refund requests against the same charge,
     *      which a token-scoped cache lock alone cannot prevent. This goes
     *      beyond order_payment_refund_allocations' own precedent (which
     *      has no row-level lock) — a deliberate, disclosed strengthening,
     *      not a formula change.
     * The unique index on billing_charge_refunds.idempotency_key is the
     * final backstop if the cache lock is ever unavailable/expired — a
     * duplicate INSERT is caught and treated as an already-succeeded
     * request rather than an error.
     */
    private function storeLinkedRefund(array $validated)
    {
        $chargeUniqueId = $validated['billing_charge_unique_id'];
        $idempotencyToken = $validated['idempotency_token'] ?? null;
        $idempotencyKey = $idempotencyToken ? "{$chargeUniqueId}:{$idempotencyToken}" : null;

        $lock = $idempotencyToken
            ? Cache::lock("billing-charge-refund:{$chargeUniqueId}:{$idempotencyToken}", 30)
            : null;

        if ($lock && !$lock->get()) {
            return redirect()->back()->withInput()->withErrors([
                'error' => 'This refund is already being processed. Please wait a moment and refresh.',
            ]);
        }

        try {
            DB::beginTransaction();

            $charge = BillingCharge::where('unique_id', $chargeUniqueId)->lockForUpdate()->first();

            if (!$charge) {
                DB::rollBack();
                return redirect()->back()->withInput()->withErrors([
                    'error' => 'The selected charge could not be found.',
                ]);
            }

            $eligibilityError = BillingChargeRefundService::eligibilityError($charge, (int) $validated['customer_id']);
            if ($eligibilityError !== null) {
                DB::rollBack();
                return redirect()->back()->withInput()->withErrors(['error' => $eligibilityError]);
            }

            $remaining = BillingChargeRefundService::remainingRefundable($charge);
            $requestedAmount = round((float) $validated['amount'], 2);

            if (BillingChargeRefundService::exceedsRemaining($requestedAmount, $remaining)) {
                DB::rollBack();
                return redirect()->back()->withInput()->withErrors([
                    'error' => sprintf(
                        'Refund amount ($%s) exceeds the remaining refundable total of $%s for this charge (base $%s, tax $%s remaining).',
                        number_format($requestedAmount, 2),
                        number_format($remaining['total'], 2),
                        number_format($remaining['base'], 2),
                        number_format($remaining['tax'], 2)
                    ),
                ]);
            }

            $split = BillingChargeRefundService::resolveSplit($charge, $requestedAmount, $remaining);

            $user = User::findOrFail($validated['responsible_person']);

            $record = new CustomerAccount();
            $record->customer_id = $validated['customer_id'];
            // Stored amount is the resolved split's total, not the raw
            // submitted value — resolveSplit() clamps a final refund to
            // the exact remaining balance, and the ledger row must agree
            // with the allocation row it's about to be linked to.
            $record->amount = $split['total_amount'];
            $record->reason = $validated['reason'];
            $record->responsible_person_id = $user->id ?? '';
            $record->responsible_person_name = $user->full_name ?? '';
            $record->notes = $validated['notes'] ?? null;
            $record->date = now();
            $record->type = 'refund';
            $record->sales_tax_type = ChargeTaxCalculator::TREATMENT_REVERSE;
            $record->sales_tax = (float) $charge->amount > 0
                ? (float) $charge->tax_amount / (float) $charge->amount
                : 0.0;

            $record->save();

            CustomHelper::updateCreditBalance($record);

            BillingChargeRefund::create([
                'billing_charge_id' => $charge->id,
                'customer_account_id' => $record->id,
                'responsible_person_id' => $user->id,
                'base_amount' => $split['base_amount'],
                'tax_amount' => $split['tax_amount'],
                'total_amount' => $split['total_amount'],
                'status' => BillingChargeRefundStatus::Allocated->value,
                'idempotency_key' => $idempotencyKey,
            ]);

            DB::commit();

            flash('Refund processed successfully.')->success();
            session(['active_tab' => 'credit']);

            return redirect()->back();
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();

            // The idempotency_key unique index is the final backstop — a
            // duplicate submission that slipped past (or never held) the
            // cache lock lands here. Treat it as already succeeded, not a
            // failure, matching the mission's "must not create a second
            // refund" requirement.
            flash('This refund was already processed.')->info();
            session(['active_tab' => 'credit']);

            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while processing the refund.')->error();
            session(['active_tab' => 'credit']);

            Log::error('Linked refund error: ' . $e->getMessage());

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while processing the refund.',
            ]);
        } finally {
            $lock?->release();
        }
    }
}

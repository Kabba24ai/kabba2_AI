<?php

namespace App\Listeners\Credit;

use App\Enums\Credit\CreditReviewTaskOutcome;
use App\Enums\Credit\CreditThresholdReviewStatus;
use App\Events\Credit\CreditThresholdExceededEvent;
use App\Models\Credit\CreditThresholdEvent;
use App\Services\Credit\CreditReviewTaskManager;
use Illuminate\Support\Facades\Log;

/**
 * Persists the durable credit-threshold event, then creates/appends the
 * management-review task. Runs synchronously after the financial posting has
 * committed (the event is dispatched via DB::afterCommit).
 *
 * Ordering guarantees, per the approved rule:
 *  1. The immutable event is ALWAYS recorded first, keyed idempotently so the
 *     same posting can never produce two events. Its survival is independent of
 *     Task Manager.
 *  2. Task creation is a best-effort side-effect. Any failure is caught,
 *     recorded on the event (task_outcome = Failed) and logged — never rethrown
 *     — so a Task-Manager problem can never turn a committed financial posting
 *     into an error, and the exception stays recoverable.
 */
class RecordCreditThresholdException
{
    public function __construct(private CreditReviewTaskManager $taskManager)
    {
    }

    public function handle(CreditThresholdExceededEvent $event): void
    {
        $s = $event->snapshot;

        try {
            $record = CreditThresholdEvent::firstOrCreate(
                ['idempotency_key' => $s->idempotencyKey],
                [
                    'customer_id'               => $s->customerId,
                    'customer_name_snapshot'    => $s->customerName,
                    'triggering_order_id'       => $s->orderId,
                    'triggering_account_row_id' => $s->accountRowId,
                    'source_type'               => $s->sourceType->value,
                    'source_detail'             => $s->sourceDetail,
                    'credit_limit_at_time'      => $s->creditLimit,
                    'balance_before'            => $s->balanceBefore,
                    'exposure_added'            => $s->exposureAdded,
                    'balance_after'             => $s->balanceAfter,
                    'amount_over_limit'         => $s->amountOverLimit,
                    'responsible_user_id'       => $s->responsibleUserId,
                    'responsible_context'       => $s->responsibleContext,
                    'occurred_at'               => $s->occurredAt,
                    'review_status'             => CreditThresholdReviewStatus::Open->value,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Failed to record credit-threshold event', [
                'idempotency_key' => $s->idempotencyKey,
                'exception'       => $e->getMessage(),
            ]);

            return;
        }

        // Duplicate posting (same key) — already recorded and already tasked.
        if (! $record->wasRecentlyCreated) {
            return;
        }

        try {
            $this->taskManager->createOrAppend($record);
        } catch (\Throwable $e) {
            $record->update(['task_outcome' => CreditReviewTaskOutcome::Failed->value]);

            Log::error('Credit-threshold event recorded but review task creation failed', [
                'event_id'   => $record->id,
                'customer_id' => $record->customer_id,
                'exception'  => $e->getMessage(),
            ]);
        }
    }
}

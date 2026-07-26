<?php

namespace App\Services\Credit;

use App\Enums\Credit\CreditReviewTaskOutcome;
use App\Enums\Credit\CreditThresholdReviewStatus;
use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskCommentType;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Models\Credit\CreditThresholdEvent;
use App\Models\Tasks\Task;
use App\Services\Billing\PrimaryBillingAdminResolver;

/**
 * Creates or appends the management-review task for a credit-threshold event.
 *
 * Policy (approved):
 *  - ONE open "Credit Account Review" task per customer. A later crossing while
 *    that task is still open is APPENDED as a comment (and its event links to
 *    the same task); a brand-new task is created only once the prior review is
 *    resolved.
 *  - Assigned to (and created by) the canonical Primary Billing Admin.
 *  - Missing-admin fallback: the task is still created, but UNASSIGNED and owned
 *    by the isolated System actor, and the event is flagged DeferredNoAdmin so
 *    it is visibly actionable and recoverable. The financial posting has already
 *    committed and is never affected.
 *
 * This service only observes committed results and creates follow-up; it never
 * touches balances or the Billing Engine.
 */
class CreditReviewTaskManager
{
    public function __construct(private PrimaryBillingAdminResolver $adminResolver)
    {
    }

    public function createOrAppend(CreditThresholdEvent $event): void
    {
        $openEvent = $this->openReviewEventForCustomer($event);

        if ($openEvent && $openEvent->task_id) {
            $this->appendToExistingTask($openEvent->task_id, $event);

            return;
        }

        $this->createNewTask($event);
    }

    /** The customer's most recent event whose linked task is still open. */
    private function openReviewEventForCustomer(CreditThresholdEvent $event): ?CreditThresholdEvent
    {
        return CreditThresholdEvent::query()
            ->where('customer_id', $event->customer_id)
            ->where('id', '!=', $event->id)
            ->whereNotNull('task_id')
            ->whereHas('task', fn ($q) => $q->open())
            ->latest('id')
            ->first();
    }

    private function appendToExistingTask(int $taskId, CreditThresholdEvent $event): void
    {
        $task = Task::find($taskId);

        if (! $task) {
            // The linked task vanished; fall back to creating a fresh one.
            $this->createNewTask($event);

            return;
        }

        $task->comments()->create([
            'user_id'      => $task->created_by_user_id,
            'comment'      => $this->appendComment($event),
            'comment_type' => TaskCommentType::Standard,
        ]);

        $task->activityLogs()->create([
            'user_id'   => $task->created_by_user_id,
            'action'    => 'comment_added',
            'new_value' => 'Credit threshold crossing appended',
        ]);

        $event->update([
            'task_id'       => $task->id,
            'task_outcome'  => CreditReviewTaskOutcome::Appended->value,
            'review_status' => CreditThresholdReviewStatus::Open->value,
        ]);
    }

    private function createNewTask(CreditThresholdEvent $event): void
    {
        $admin = $this->adminResolver->primary();

        if ($admin) {
            $creatorId  = $admin->id;
            $assigneeId = $admin->id;
            $outcome    = CreditReviewTaskOutcome::Created;
        } else {
            // Missing-admin fallback: system-owned, unassigned, flagged.
            $creatorId  = SystemActorResolver::id();
            $assigneeId = null;
            $outcome    = CreditReviewTaskOutcome::DeferredNoAdmin;
        }

        $task = Task::create([
            'category'            => TaskCategory::Billing,
            'title'               => 'Credit Account Review — ' . ($event->customer_name_snapshot ?: 'Customer'),
            'description'         => $this->buildDescription($event),
            'priority'            => TaskPriority::High,
            'status'              => TaskStatus::Open,
            'created_by_user_id'  => $creatorId,
            'assigned_to_user_id' => $assigneeId,
            // Canonical order→customer linkage (customer derived from the event).
            'related_order_id'    => $event->triggering_order_id,
            'related_customer_id' => $event->customer_id,
        ]);

        $task->activityLogs()->create([
            'user_id'   => $creatorId,
            'action'    => 'task_created',
            'new_value' => $task->title,
        ]);

        $event->update([
            'task_id'       => $task->id,
            'task_outcome'  => $outcome->value,
            'review_status' => CreditThresholdReviewStatus::Open->value,
        ]);
    }

    private function buildDescription(CreditThresholdEvent $event): string
    {
        $lines = [
            'This customer exceeded the approved credit threshold. Review the customer’s credit account and determine whether action is required.',
            '',
            'Customer: ' . ($event->customer_name_snapshot ?: 'Customer'),
        ];

        if ($event->triggering_order_id) {
            $lines[] = 'Order: #' . $event->triggering_order_id;
        }

        $lines = array_merge($lines, [
            'Approved Credit Limit: ' . $this->money($event->credit_limit_at_time),
            'Balance Before Transaction: ' . $this->money($event->balance_before),
            'Added Exposure: ' . $this->money($event->exposure_added),
            'Resulting Account Balance: ' . $this->money($event->balance_after),
            'Amount Over Credit Limit: ' . $this->money($event->amount_over_limit),
            'Date/Time: ' . $event->occurred_at?->format('M j, Y g:i A'),
            'Source: ' . ($event->source_detail ?: $event->source_type?->label()),
            'Reason: A posted transaction pushed the outstanding balance over the approved credit limit.',
            '',
            'Management considerations (review options — NOT automatic outcomes):',
            '• Raise the credit limit',
            '• Leave the limit unchanged',
            '• Request payment',
            '• Contact the customer',
            '• Suspend new rentals',
            '• Suspend additional on-account exposure',
            '• Escalate for owner / senior management review',
        ]);

        return implode("\n", $lines);
    }

    private function appendComment(CreditThresholdEvent $event): string
    {
        $parts = ['Additional credit threshold crossing on ' . $event->occurred_at?->format('M j, Y g:i A') . '.'];

        if ($event->triggering_order_id) {
            $parts[] = 'Order #' . $event->triggering_order_id . '.';
        }

        $parts[] = 'Added ' . $this->money($event->exposure_added)
            . '; balance now ' . $this->money($event->balance_after)
            . ' (' . $this->money($event->amount_over_limit) . ' over the '
            . $this->money($event->credit_limit_at_time) . ' limit).';

        return implode(' ', $parts);
    }

    private function money($value): string
    {
        return '$' . number_format((float) $value, 2);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit Threshold Exception events — the durable, immutable historical record
 * of every qualifying crossing of a customer's APPROVED credit limit.
 *
 * This is an audit/event log, NOT a second balance ledger: it stores only
 * snapshots + foreign keys to existing records and never participates in any
 * balance calculation. Its survival is deliberately decoupled from Task Manager
 * — the event is always written even if the management-review task cannot be
 * created (e.g. no Primary Billing Admin configured), and `task_outcome`
 * records the task side-effect's fate so a failure is visible and recoverable.
 *
 * Idempotency: one event per qualifying POSTING. `idempotency_key` is
 * `order:{order_id}` when the exposure came from an order (so a multi-product
 * order yields ONE event, not one per line), else `acct:{customer_accounts.id}`
 * for standalone A/R charges. The same financial posting can never create two
 * events, even on retry/double-submit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_threshold_events', function (Blueprint $table) {
            $table->id();

            // One event per posting; unique so a retried/duplicated posting
            // cannot double-record.
            $table->string('idempotency_key')->unique();

            // Subject. Nullable + nullOnDelete so the historical snapshot
            // survives a customer deletion; customer_name_snapshot preserves
            // the display identity regardless.
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name_snapshot')->nullable();

            // Context of the triggering posting.
            $table->foreignId('triggering_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('triggering_account_row_id')->nullable()->constrained('customer_accounts')->nullOnDelete();
            $table->string('source_type')->default('other');   // CreditThresholdSourceType
            $table->string('source_detail')->nullable();        // e.g. the ledger row reason

            // Financial snapshots (historical values, never recomputed).
            $table->decimal('credit_limit_at_time', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('exposure_added', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->decimal('amount_over_limit', 15, 2);

            // Who / what completed the transaction (nullable — customer or guest
            // checkout has no staff User).
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsible_context')->nullable();  // e.g. customer_checkout / guest / impersonation

            $table->timestamp('occurred_at');

            // Management-review linkage. Task may be null (deferred/failed); the
            // event stands alone regardless.
            $table->foreignId('task_id')->nullable()->constrained('daily_tasks')->nullOnDelete();
            $table->string('task_outcome')->nullable();         // CreditReviewTaskOutcome
            $table->string('review_status')->default('open');   // CreditThresholdReviewStatus (mirrors the task)

            $table->timestamps();

            $table->index(['customer_id', 'review_status'], 'cte_customer_review_idx');
            $table->index('occurred_at', 'cte_occurred_idx');
            $table->index('task_outcome', 'cte_outcome_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_threshold_events');
    }
};

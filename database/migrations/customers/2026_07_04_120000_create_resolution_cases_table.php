<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3.3 — Customer Resolution Center Foundation.
     *
     * One row per guided resolution session — doubles as the full audit
     * trail, the same "the ledger is the log" pattern `customer_credits`
     * already established (Phase 3.0). Snapshot columns are informational
     * only, capturing what was true when the case was opened; they are
     * never read as a live balance — CustomerCreditService/CustomHelper/
     * Order remain the only live sources of truth for those figures.
     */
    public function up(): void
    {
        Schema::create('resolution_cases', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->text('issue');

            // Snapshot at case-open time — informational only.
            $table->decimal('balance_snapshot', 15, 2)->nullable();
            $table->decimal('store_credit_snapshot', 15, 2)->nullable();
            $table->string('payment_method_snapshot')->nullable();

            // Decision tree answers.
            $table->boolean('can_reschedule')->nullable();
            $table->boolean('credit_would_satisfy')->nullable();

            // Recommendation produced by ResolutionDecisionEngine.
            $table->string('recommended_resolution')->nullable();
            $table->text('recommended_next_step')->nullable();

            // What the employee actually did.
            $table->string('employee_decision')->nullable();
            $table->text('employee_decision_detail')->nullable();
            $table->unsignedBigInteger('manager_override_user_id')->nullable();
            $table->text('manager_override_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('outcome')->default('pending');

            // If "Issue Store Credit" was approved, the resulting ledger row.
            $table->unsignedBigInteger('credit_issued_id')->nullable();

            $table->unsignedBigInteger('responsible_person_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('manager_override_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('credit_issued_id')->references('id')->on('customer_credits')->nullOnDelete();
            $table->foreign('responsible_person_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['customer_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_cases');
    }
};

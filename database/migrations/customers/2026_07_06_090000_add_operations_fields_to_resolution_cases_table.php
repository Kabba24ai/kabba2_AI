<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 3.6 — Customer Resolution Operations Center. Purely additive
// operational-triage fields — see PHASE_3_6_OPERATIONS_AUDIT.md §4 for why
// each was needed and why none could reuse an existing column (in
// particular: `status` is a new, separate field from the existing
// `outcome`, not a redefinition of it).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resolution_cases', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('issue_category');
            $table->string('status')->default('open')->after('outcome');
            $table->string('waiting_on')->nullable()->after('status');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable()->after('responsible_person_id');
            $table->unsignedBigInteger('store_id')->nullable()->after('assigned_to_user_id');
            $table->timestamp('completed_at')->nullable()->after('credit_issued_id');

            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('resolution_cases', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropForeign(['store_id']);
            $table->dropIndex(['status', 'priority']);
            $table->dropColumn(['priority', 'status', 'waiting_on', 'assigned_to_user_id', 'store_id', 'completed_at']);
        });
    }
};

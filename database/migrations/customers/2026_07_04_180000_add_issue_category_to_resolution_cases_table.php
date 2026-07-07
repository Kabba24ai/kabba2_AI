<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3.5 — Manual Resolution Scenario.
     *
     * Additive only. `issue` (existing, Phase 3.3) remains the free-text
     * description of what the customer is asking for, captured when any
     * case is opened. `issue_category` is new — a constrained category
     * (see ManualResolutionScenario::CATEGORY_LABELS) specific to the
     * Manual Resolution scenario's guided flow. Nullable: the
     * Cancellation/Refund scenario never sets it.
     */
    public function up(): void
    {
        Schema::table('resolution_cases', function (Blueprint $table) {
            $table->string('issue_category')->nullable()->after('issue');
        });
    }

    public function down(): void
    {
        Schema::table('resolution_cases', function (Blueprint $table) {
            $table->dropColumn('issue_category');
        });
    }
};

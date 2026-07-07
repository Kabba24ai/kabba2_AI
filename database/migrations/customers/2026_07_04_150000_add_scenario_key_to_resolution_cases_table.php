<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3.4 — Operational Knowledge Framework.
     *
     * Additive only. Identifies which ResolutionScenario governs a case —
     * the one schema change needed for the framework's registry to resolve
     * the correct scenario for an existing case. Defaults to the reference
     * implementation's key so this has zero effect on Phase 3.3 behavior.
     */
    public function up(): void
    {
        Schema::table('resolution_cases', function (Blueprint $table) {
            $table->string('scenario_key')->default('cancellation_refund')->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('resolution_cases', function (Blueprint $table) {
            $table->dropColumn('scenario_key');
        });
    }
};

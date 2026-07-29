<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver designations for AI dispatch decision-making, plus a contract-driver flag.
 *
 * - dispatch_ai_driver_capabilities.designation: a driver's mission tier
 *   ('primary' | 'secondary' | 'alternate') — how eagerly the AI should assign them.
 * - users.is_contract_driver: marks an external contract driver. Such users are
 *   flagged is_driver=true so they work everywhere in Dispatch, but are excluded
 *   from the HRM employee list (User::employees scope) — they are never treated as
 *   staff. Dispatch-only for now (no mobile login onboarding).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_ai_driver_capabilities', function (Blueprint $table) {
            $table->string('designation')->default('primary')->after('skill_rating');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_contract_driver')->default(false)->after('cdl_b');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_ai_driver_capabilities', function (Blueprint $table) {
            $table->dropColumn('designation');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_contract_driver');
        });
    }
};

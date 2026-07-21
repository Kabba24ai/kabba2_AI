<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipment-specific Reported-Problem templates — built by EXTENDING the
 * existing symptom library (service_symptom_* shipped 2026_07_13), NOT a
 * parallel problem_* taxonomy. The library already IS the spec's Categories
 * (service_symptom_categories) and Items (service_symptoms), already seeded
 * with the 9 categories / 34+ symptoms, and service_ticket_complaints
 * already snapshots name + system_group as plain strings (the "tickets are
 * frozen" requirement). This migration adds only the three genuine gaps:
 *
 *  1. service_symptom_profiles.description — the spec's template description.
 *  2. service_symptom_profile_symptoms.sort_order — per-template item order
 *     (the spec's builder assembles an explicit, ordered item list; the
 *     existing include/exclude `mode` rows gain an order). The legacy
 *     "include whole category" assembly is untouched and still works.
 *  3. equipment.service_symptom_profile_id — per-UNIT attachment (the spec's
 *     problem_template_id). A single nullable FK enforces "at most one
 *     template per unit" by construction. Intake resolution precedence
 *     becomes: unit FK → product/product-category profile (legacy fallback)
 *     → full library. Existing product/category targeting on the profile is
 *     left in place as that fallback — nothing that works today breaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_symptom_profiles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        Schema::table('service_symptom_profile_symptoms', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('mode');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('service_symptom_profile_id')->nullable()->after('assigned_product_id')
                ->constrained('service_symptom_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_symptom_profile_id');
        });

        Schema::table('service_symptom_profile_symptoms', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('service_symptom_profiles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};

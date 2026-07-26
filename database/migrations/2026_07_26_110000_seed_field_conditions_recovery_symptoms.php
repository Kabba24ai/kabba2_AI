<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "Field Conditions & Recovery" category + its symptoms to the shared
 * Service symptom library so Field Service intake can report operating
 * conditions (distinct from equipment symptoms) through the SAME canonical
 * problem engine and complaint persistence — no separate field problems table.
 *
 * Seeded in a migration because deploys run migrations, not seeders. Idempotent:
 * skips if the category already exists, and never duplicates a symptom name.
 */
return new class extends Migration {
    public function up(): void
    {
        $name = 'Field Conditions & Recovery';
        $now  = now();

        // Find-or-create the category — never skip the whole migration just
        // because the category exists; each missing symptom is inserted below.
        $existing = DB::table('service_symptom_categories')->where('name', $name)->first();
        if ($existing) {
            $categoryId = $existing->id;
            if (!$existing->is_active) {
                DB::table('service_symptom_categories')->where('id', $categoryId)
                    ->update(['is_active' => true, 'updated_at' => $now]);
            }
        } else {
            $categoryId = DB::table('service_symptom_categories')->insertGetId([
                'name'          => $name,
                'display_order' => 100, // after the existing library (Glass = 90)
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // Operating conditions / recovery issues — deliberately NOT equipment
        // symptoms (e.g. "Track came off" stays in Tracks & Undercarriage).
        $symptoms = [
            'Machine stuck in mud',
            'Machine stuck in a field',
            'Machine stranded in water or stream',
            'Machine inaccessible',
            'Machine cannot be safely moved',
            'Recovery equipment required',
            'Complex recovery conditions',
            'Restricted job-site access',
        ];

        $order = 10;
        foreach ($symptoms as $symptom) {
            // Symptom names are unique library-wide — never duplicate one that
            // somehow already exists.
            if (DB::table('service_symptoms')->where('name', $symptom)->exists()) {
                continue;
            }
            DB::table('service_symptoms')->insert([
                'service_symptom_category_id' => $categoryId,
                'name'          => $symptom,
                'display_order' => $order,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $order += 10;
        }
    }

    public function down(): void
    {
        $category = DB::table('service_symptom_categories')->where('name', 'Field Conditions & Recovery')->first();
        if (!$category) {
            return;
        }
        DB::table('service_symptoms')->where('service_symptom_category_id', $category->id)->delete();
        DB::table('service_symptom_categories')->where('id', $category->id)->delete();
    }
};

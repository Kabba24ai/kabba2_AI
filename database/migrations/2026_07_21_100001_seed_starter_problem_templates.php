<?php

use App\Enums\Service\ServiceSymptomProfileSymptomMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Starter Reported-Problem templates (Equipment problem-templates) — two
 * explicit, ordered templates assembled from the already-seeded symptom
 * library, so the new builder/intake path has real examples the moment it
 * ships. Deploys run migrations, never seeders, so this lives here.
 *
 * Templates are NOT attached to any unit — attachment is a deliberate admin
 * action on the Equipment Templates screen (and unit inventory varies by
 * environment). Skipped cleanly if a template of the same name already
 * exists or the referenced symptoms aren't present.
 */
return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            'Skid Steer' => [
                'Will not start', 'Engine overheating', 'Low power',
                'Hydraulic leak', 'Auxiliary hydraulics not working', "Attachment won't operate",
                'Track came off', "Machine won't travel",
                'Controls unresponsive', 'Joystick issue',
            ],
            'Mini Excavator' => [
                'Will not start', 'Excessive smoke',
                'Hydraulic leak', 'Slow hydraulic response', 'Hose damaged',
                'Track damaged', 'Undercarriage noise',
                'Controls unresponsive',
            ],
        ];

        $now = now();

        foreach ($templates as $name => $symptomNames) {
            if (DB::table('service_symptom_profiles')->where('name', $name)->exists()) {
                continue;
            }

            $symptomIds = DB::table('service_symptoms')
                ->whereIn('name', $symptomNames)
                ->pluck('id', 'name');

            if ($symptomIds->isEmpty()) {
                continue;
            }

            $profileId = DB::table('service_symptom_profiles')->insertGetId([
                'name'          => $name,
                'description'   => "Starter template for {$name} units.",
                'display_order' => (int) DB::table('service_symptom_profiles')->max('display_order') + 10,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $order = 0;
            foreach ($symptomNames as $symptomName) {
                if (! isset($symptomIds[$symptomName])) {
                    continue;
                }
                $order += 10;
                DB::table('service_symptom_profile_symptoms')->insert([
                    'service_symptom_profile_id' => $profileId,
                    'service_symptom_id'         => $symptomIds[$symptomName],
                    'mode'                       => ServiceSymptomProfileSymptomMode::Include->value,
                    'sort_order'                 => $order,
                    'created_at'                 => $now,
                    'updated_at'                 => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('service_symptom_profiles')
            ->whereIn('name', ['Skid Steer', 'Mini Excavator'])
            ->delete(); // cascades to profile_symptoms via FK
    }
};

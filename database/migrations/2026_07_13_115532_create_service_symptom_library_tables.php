<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Categorized symptom library (service_symptom_categories → service_symptoms)
// plus equipment symptom profiles (service_symptom_profiles) that assemble a
// machine-specific checklist from whole included categories with optional
// per-symptom additions/exclusions (service_symptom_profile_categories /
// service_symptom_profile_symptoms). Supersedes the flat 7-group
// ServiceComplaintType library for new intake, without touching it — old
// tickets keep their service_complaint_type_id snapshot exactly as-is.
// The library is seeded here because the deploy pipeline runs migrations,
// never seeders.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_symptom_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_symptoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_symptom_category_id')->constrained('service_symptom_categories')->cascadeOnDelete();
            // Exists only once in the library — reused across as many
            // profiles/categories as apply, never duplicated per equipment type.
            $table->string('name')->unique();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['service_symptom_category_id', 'display_order']);
        });

        Schema::create('service_symptom_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Both optional: a profile may target a whole equipment category,
            // one specific product (more specific — wins at resolution time),
            // or neither (unused until assigned).
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Included Categories — bulk-draws every active symptom in each
        // included category into the profile's checklist.
        Schema::create('service_symptom_profile_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_symptom_profile_id')
                ->constrained('service_symptom_profiles', indexName: 'symptom_profile_categories_profile_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('service_symptom_category_id')
                ->constrained('service_symptom_categories', indexName: 'symptom_profile_categories_category_id_foreign')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['service_symptom_profile_id', 'service_symptom_category_id'], 'symptom_profile_category_unique');
        });

        // Individual Additions / Individual Exclusions — per-symptom
        // overrides on top of the included categories (mode: include|exclude,
        // App\Enums\Service\ServiceSymptomProfileSymptomMode). Not full
        // profile inheritance (out of scope this phase) — but nothing here
        // blocks adding a parent_profile_id later.
        Schema::create('service_symptom_profile_symptoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_symptom_profile_id')
                ->constrained('service_symptom_profiles', indexName: 'symptom_profile_symptoms_profile_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('service_symptom_id')->constrained('service_symptoms')->cascadeOnDelete();
            $table->string('mode');
            $table->timestamps();
            $table->unique(['service_symptom_profile_id', 'service_symptom_id'], 'symptom_profile_symptom_unique');
        });

        // Additive snapshot link for new tickets — sits alongside the legacy
        // service_complaint_type_id (untouched) so historical rows keep
        // pointing at the retired library exactly as they always have.
        Schema::table('service_ticket_complaints', function (Blueprint $table) {
            $table->foreignId('service_symptom_id')->nullable()->after('service_complaint_type_id')
                ->constrained('service_symptoms')->nullOnDelete();
        });

        $this->seedSymptomLibrary();
    }

    public function down(): void
    {
        Schema::table('service_ticket_complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_symptom_id');
        });
        Schema::dropIfExists('service_symptom_profile_symptoms');
        Schema::dropIfExists('service_symptom_profile_categories');
        Schema::dropIfExists('service_symptom_profiles');
        Schema::dropIfExists('service_symptoms');
        Schema::dropIfExists('service_symptom_categories');
    }

    private function seedSymptomLibrary(): void
    {
        $now = now();

        // Categories: the 7 existing ComplaintSystemGroup labels, migrated
        // 1:1 with their current names/grouping, plus one new equipment-
        // specific category (Boom Lift) and one new reusable category
        // (Glass) — the "one real example" proving the architecture end to
        // end. Everything beyond this is left for the business to populate
        // via a future admin builder, not invented here.
        $categories = [
            'Engine & Starting'      => 10,
            'Hydraulic System'       => 20,
            'Tracks & Undercarriage' => 30,
            'Controls'               => 40,
            'Cab'                    => 50,
            'Physical Damage'        => 60,
            'Other'                  => 70,
            'Boom Lift'              => 80,
            'Glass'                  => 90,
        ];

        $categoryIds = [];
        foreach ($categories as $name => $order) {
            $categoryIds[$name] = DB::table('service_symptom_categories')->insertGetId([
                'name'           => $name,
                'display_order'  => $order,
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // Symptoms: the existing 34 complaints, preserving their current
        // names and grouping, plus the Boom Lift / Glass examples drawn
        // directly from the mission brief's own symptom lists (not invented).
        $library = [
            'Engine & Starting' => [
                'Will not start', 'Hard starting', 'Engine shuts down', 'Engine overheating',
                'Warning light', 'Low power', 'Excessive smoke', 'Fluid leak',
            ],
            'Hydraulic System' => [
                'Hydraulic leak', 'Auxiliary hydraulics not working',
                "Attachment won't operate", 'Slow hydraulic response', 'Hose damaged',
            ],
            'Tracks & Undercarriage' => [
                'Track came off', 'Track damaged', "Machine won't travel",
                'Pulls to one side', 'Undercarriage noise',
            ],
            'Controls' => [
                'Controls unresponsive', 'Joystick issue', 'Safety interlock problem', 'Parking brake issue',
            ],
            'Cab' => [
                'Door damaged', 'Broken glass', 'Windshield wiper', 'Heater',
                'Air conditioner', 'Seat damage', 'Display malfunction',
            ],
            'Physical Damage' => [
                'Body damage', 'Missing hardware', 'Attachment damage', 'Structural damage',
            ],
            'Other' => [
                'Other / Not Listed',
            ],
            'Boom Lift' => [
                'Platform will not raise', 'Platform will not lower', 'Platform will not rotate',
                'Platform will not level', 'Upper controls not responding', 'Lower controls not responding',
            ],
            'Glass' => [
                'Windshield cracked', 'Door glass broken', 'Side glass broken', 'Glass missing',
            ],
        ];

        $symptomIds = [];
        foreach ($library as $category => $symptoms) {
            foreach (array_values($symptoms) as $order => $name) {
                $symptomIds[$name] = DB::table('service_symptoms')->insertGetId([
                    'service_symptom_category_id' => $categoryIds[$category],
                    'name'                         => $name,
                    'display_order'                => $order + 1,
                    'is_active'                     => true,
                    'created_at'                    => $now,
                    'updated_at'                    => $now,
                ]);
            }
        }

        // One real equipment profile: Boom Lift, assigned to the actual
        // "Boom Lifts" product category if this environment's product
        // taxonomy has one — skipped (not failed) otherwise, since the
        // symptom library itself is still fully valid without it.
        $boomLiftCategoryId = DB::table('product_categories')->where('title', 'Boom Lifts')->value('id');

        $profileId = DB::table('service_symptom_profiles')->insertGetId([
            'name'                => 'Boom Lift',
            'product_category_id' => $boomLiftCategoryId,
            'product_id'          => null,
            'display_order'       => 10,
            'is_active'           => true,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        foreach (['Engine & Starting', 'Hydraulic System', 'Controls', 'Boom Lift'] as $category) {
            DB::table('service_symptom_profile_categories')->insert([
                'service_symptom_profile_id'  => $profileId,
                'service_symptom_category_id' => $categoryIds[$category],
                'created_at'                   => $now,
                'updated_at'                   => $now,
            ]);
        }
    }
};

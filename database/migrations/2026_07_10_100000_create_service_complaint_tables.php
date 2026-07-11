<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Structured complaint intake: a managed complaint library
// (service_complaint_types), one row per selected complaint on a ticket
// (service_ticket_complaints), and capability flags on equipment so
// complaint lists adapt to what components a machine actually has.
// The library is seeded here because the deploy pipeline runs migrations,
// never seeders.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_complaint_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('system_group')->index();
            // Capability keys (EquipmentCapability) ALL required for display
            $table->json('required_capabilities')->nullable();
            // null = applies to every product / category
            $table->json('applicable_product_ids')->nullable();
            $table->json('applicable_category_ids')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_ticket_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('service_complaint_type_id')->nullable()
                ->constrained('service_complaint_types')->nullOnDelete();
            // Snapshots survive library renames — the ticket keeps what was reported
            $table->string('name');
            $table->string('system_group');
            $table->timestamps();
        });

        Schema::table('equipment', function (Blueprint $table) {
            // null = capabilities unknown (machine hides nothing);
            // [] or list = enforce complaint capability requirements
            $table->json('capabilities')->nullable()->after('assigned_product_id');
        });

        $this->seedComplaintLibrary();
    }

    public function down(): void
    {
        Schema::table('equipment', fn (Blueprint $table) => $table->dropColumn('capabilities'));
        Schema::dropIfExists('service_ticket_complaints');
        Schema::dropIfExists('service_complaint_types');
    }

    private function seedComplaintLibrary(): void
    {
        $library = [
            'engine_starting' => [
                'Will not start', 'Hard starting', 'Engine shuts down', 'Engine overheating',
                'Warning light', 'Low power', 'Excessive smoke', 'Fluid leak',
            ],
            'hydraulic' => [
                'Hydraulic leak',
                ['Auxiliary hydraulics not working', ['auxiliary_hydraulics']],
                "Attachment won't operate", 'Slow hydraulic response', 'Hose damaged',
            ],
            'tracks_undercarriage' => [
                ['Track came off', ['rubber_tracks']],
                ['Track damaged', ['rubber_tracks']],
                "Machine won't travel", 'Pulls to one side', 'Undercarriage noise',
            ],
            'controls' => [
                'Controls unresponsive', 'Joystick issue', 'Safety interlock problem', 'Parking brake issue',
            ],
            'cab' => [
                ['Door damaged', ['enclosed_cab']],
                ['Broken glass', ['glass_door']],
                ['Windshield wiper', ['windshield_wiper']],
                ['Heater', ['heater']],
                ['Air conditioner', ['air_conditioning']],
                'Seat damage', 'Display malfunction',
            ],
            'physical_damage' => [
                'Body damage', 'Missing hardware', 'Attachment damage', 'Structural damage',
            ],
            'other' => [
                'Other / Not Listed',
            ],
        ];

        $now = now();
        $rows = [];
        foreach ($library as $group => $complaints) {
            foreach (array_values($complaints) as $order => $complaint) {
                [$name, $capabilities] = is_array($complaint) ? $complaint : [$complaint, null];
                $rows[] = [
                    'name'                    => $name,
                    'system_group'            => $group,
                    'required_capabilities'   => $capabilities ? json_encode($capabilities) : null,
                    'applicable_product_ids'  => null,
                    'applicable_category_ids' => null,
                    'display_order'           => $order + 1,
                    'is_active'               => true,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ];
            }
        }

        DB::table('service_complaint_types')->insert($rows);
    }
};

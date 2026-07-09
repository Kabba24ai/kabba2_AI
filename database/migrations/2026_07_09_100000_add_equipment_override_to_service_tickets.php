<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Equipment ID Override (intake): equipment_id keeps meaning the unit being
// serviced — everything downstream (labor, parts, history) already follows it.
// When the order carries the wrong unit, order_equipment_id preserves the
// original order equipment and the override_* columns record the correction.
// The rental order itself is never touched.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->foreignId('order_equipment_id')->nullable()->after('equipment_id')
                ->constrained('equipment')->nullOnDelete();
            $table->boolean('equipment_override')->default(false)->after('order_equipment_id');
            $table->string('equipment_override_reason')->nullable()->after('equipment_override');
            $table->foreignId('equipment_override_by')->nullable()->after('equipment_override_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('equipment_override_at')->nullable()->after('equipment_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_equipment_id');
            $table->dropConstrainedForeignId('equipment_override_by');
            $table->dropColumn(['equipment_override', 'equipment_override_reason', 'equipment_override_at']);
        });
    }
};

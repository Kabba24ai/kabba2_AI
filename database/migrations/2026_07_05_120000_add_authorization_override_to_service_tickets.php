<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            // Manager override of the repair authorization gate (Phase 2E.5).
            // Distinct from the parts-deposit override: this forces the whole
            // gate open, with a mandatory reason for the audit trail.
            $table->boolean('authorization_override')->default(false)->after('repair_authorization_notes');
            $table->string('authorization_override_reason')->nullable()->after('authorization_override');
            $table->foreignId('authorization_override_by')->nullable()->after('authorization_override_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('authorization_override_at')->nullable()->after('authorization_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('authorization_override_by');
            $table->dropColumn(['authorization_override', 'authorization_override_reason', 'authorization_override_at']);
        });
    }
};

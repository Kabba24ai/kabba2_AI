<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 3.6 — Customer Resolution Operations Center. The "Audit Trail"
// requirement's data model, copying the {action, field, old_value,
// new_value, user_id} shape already independently proven by
// DispatchAuditLog and TaskActivityLog elsewhere in this codebase — no
// shared base class/package exists to extend instead. See
// PHASE_3_6_OPERATIONS_AUDIT.md §4.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_case_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resolution_case_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->foreign('resolution_case_id')->references('id')->on('resolution_cases')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(
                ['resolution_case_id', 'created_at'],
                'rcal_case_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_case_activity_logs');
    }
};

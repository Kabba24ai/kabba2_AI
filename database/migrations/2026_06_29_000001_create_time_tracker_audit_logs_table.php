<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_tracker_audit_logs', function (Blueprint $table) {
            $table->id();

            // Context — denormalized for efficient store-level and employee-level queries
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();

            // Actor — who performed the action
            $table->string('actor_type', 16)->default('user'); // 'user' | 'system'
            $table->unsignedBigInteger('actor_id')->nullable(); // null when actor_type = 'system'

            // Entity — what record was affected
            $table->string('entity_type', 32);               // 'time_entry' | 'time_entry_break' | 'vacation_request' | 'work_schedule' | 'system_setting'
            $table->unsignedBigInteger('entity_id')->nullable(); // null for system-setting events (no row)

            // Change detail
            $table->string('action', 64);                    // e.g. 'time_entry_clock_out_edited'
            $table->string('field', 64)->nullable();         // column that changed — null for record-level events
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            // Annotation
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes — no FK constraints so audit records survive entity deletion
            $table->index('employee_id');
            $table->index('actor_id');
            $table->index(['entity_type', 'entity_id'], 'ttal_entity_idx');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_tracker_audit_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clickable task status system (Task Status Bar design, 2026-07-21):
 * waiting_reason records why a task is on hold (required when status moves
 * to Waiting, cleared when it leaves), and parent_task_id links the
 * sub-task that "Help Needed" spins up back to the task it assists.
 * status itself is already a plain string column — the new help_needed
 * value needs no schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->string('waiting_reason')->nullable()->after('status');
            $table->foreignId('parent_task_id')->nullable()->after('related_equipment_id')
                ->constrained('daily_tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_task_id');
            $table->dropColumn('waiting_reason');
        });
    }
};

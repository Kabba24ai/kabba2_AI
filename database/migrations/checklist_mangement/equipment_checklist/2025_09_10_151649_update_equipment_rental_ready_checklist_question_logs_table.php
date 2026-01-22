<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'equipment_rental_ready_checklist_question_logs';
        $foreignKeyName = 'eq_chk_q_fk';

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $foreignKeyName) {
            // Check if the foreign key exists before dropping
            $fkExists = DB::select(
                "SELECT CONSTRAINT_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?",
                [$tableName, $foreignKeyName]
            );

            if (!empty($fkExists)) {
                $table->dropForeign($foreignKeyName);
            }

            // Drop the column if it exists
            if (Schema::hasColumn($tableName, 'equipment_checklist_question_id')) {
                $table->dropColumn('equipment_checklist_question_id');
            }

            // Add new columns
            $table->string('inspector_name')->nullable()->after('action_user_name');
            $table->date('inspection_date')->nullable()->after('inspector_name');
            $table->decimal('equipment_hours', 8, 1)->nullable()->after('inspection_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'equipment_rental_ready_checklist_question_logs';
        $foreignKeyName = 'eq_chk_q_fk';

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $foreignKeyName) {
            // Rollback: add the column back
            if (!Schema::hasColumn($tableName, 'equipment_checklist_question_id')) {
                $table->foreignId('equipment_checklist_question_id')->nullable()
                    ->constrained('equipment_rental_ready_checklist_questions', 'id', $foreignKeyName)
                    ->nullOnDelete();
            }

            // Drop newly added columns
            foreach (['inspector_name', 'inspection_date', 'equipment_hours'] as $col) {
                if (Schema::hasColumn($tableName, $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

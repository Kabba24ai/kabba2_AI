<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-2 (Phase 3): prevent duplicate question-in-template rows at the DB layer.
     *
     * customer_admin_template_questions has no soft-deletes, so a plain composite
     * unique key on (template_id, question_id) is sufficient.
     *
     * rental_ready_checklist_template_questions DOES have soft-deletes, and
     * TemplateCrudService::updateWithReplacedQuestions() soft-deletes every existing
     * row for a template and recreates fresh rows with the same (template_id,
     * question_id) pairs on every single template save — confirmed in
     * rc_kabba_7_7_26: 950 of 1306 total rows are already soft-deleted from this
     * exact churn. A plain composite unique key would immediately break every
     * template edit, since the soft-deleted row physically remains and collides
     * with the newly-created one. A generated column that is a fixed value only
     * when deleted_at IS NULL (and NULL — excluded from uniqueness — otherwise)
     * lets MySQL enforce "at most one active row per pair" while leaving any number
     * of soft-deleted rows for the same pair unconstrained.
     * See docs/checklist-system-audit/P3_3_DB2_UNIQUE_CONSTRAINT.md.
     */
    public function up(): void
    {
        Schema::table('rental_ready_checklist_template_questions', function (Blueprint $table) {
            $table->integer('active_pair_key')
                ->nullable()
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)')
                ->after('deleted_at');
        });

        Schema::table('rental_ready_checklist_template_questions', function (Blueprint $table) {
            $table->unique(['template_id', 'question_id', 'active_pair_key'], 'rr_template_questions_active_unique');
        });

        Schema::table('customer_admin_template_questions', function (Blueprint $table) {
            $table->unique(['template_id', 'question_id'], 'ca_template_questions_unique');
        });
    }

    public function down(): void
    {
        // Adding each composite unique key made MySQL silently drop the plain
        // single-column index InnoDB had auto-created to support the template_id
        // foreign key (no longer needed once the new composite index, which also
        // starts with template_id, could serve that purpose instead). Re-add an
        // equivalent plain index before dropping the unique key, or the DROP INDEX
        // fails with "needed in a foreign key constraint".
        Schema::table('customer_admin_template_questions', function (Blueprint $table) {
            $table->index('template_id');
            $table->dropUnique('ca_template_questions_unique');
        });

        Schema::table('rental_ready_checklist_template_questions', function (Blueprint $table) {
            $table->index('template_id');
            $table->dropUnique('rr_template_questions_active_unique');
            $table->dropColumn('active_pair_key');
        });
    }
};

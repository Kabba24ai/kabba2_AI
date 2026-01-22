<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('rental_ready_checklist_categories', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('rental_ready_checklist_questions', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('rental_ready_checklist_question_answers', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('rental_ready_checklist_template_questions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('rental_ready_checklist_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('rental_ready_checklist_questions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('rental_ready_checklist_question_answers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('rental_ready_checklist_template_questions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

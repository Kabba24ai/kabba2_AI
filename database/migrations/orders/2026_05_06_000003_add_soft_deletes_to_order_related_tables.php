<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_histories', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_notes', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_media', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_extra_charges', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_product_checklist_questions', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('order_product_checklist_question_answers', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('equipment_soft_assigns', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_soft_assigns', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_product_checklist_question_answers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_product_checklist_questions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_extra_charges', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_media', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_notes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

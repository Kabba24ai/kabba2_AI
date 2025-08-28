<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_questions', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('name');
            $table->string('category_id')->comment('References customer_admin_categories.unique_id');
            $table->boolean('required')->default(false);
            $table->string('delivery_text')->comment('Text shown for delivery (e.g., "Keys Delivered")');
            $table->string('return_text')->comment('Text shown for return (e.g., "Keys Returned")');
            $table->boolean('sync_texts')->default(true)->comment('If true, delivery and return texts are synced');
            $table->json('answer_sync_map')->nullable()->comment('Maps answer index to sync status {0: true, 1: false}');
            $table->timestamps();

            $table->index('category_id', 'idx_customer_questions_category');
            $table->index('required', 'idx_customer_questions_required');
            $table->index('name', 'idx_customer_questions_name');

            $table->foreign('category_id', 'fk_customer_questions_category')
                  ->references('unique_id')
                  ->on('customer_admin_categories')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_questions');
    }
};

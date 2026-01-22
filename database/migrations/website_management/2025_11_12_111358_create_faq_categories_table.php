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
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique(); // unique identifier
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->boolean('default_expand')->default(0);
            $table->unsignedBigInteger('category_icon_media_id')->nullable();
            $table->integer('category_index_number')->nullable();
            $table->timestamps();

            // Foreign key relationship to 'media' table
            $table->foreign('category_icon_media_id')
                ->references('id')
                ->on('media')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_categories');
    }
};

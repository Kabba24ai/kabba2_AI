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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_content')->nullable();
            $table->longText('content')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            $table->unsignedBigInteger('media_id')->nullable(); // Foreign key for media
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->integer('sort_order')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();


            // Optional foreign key constraints
            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};

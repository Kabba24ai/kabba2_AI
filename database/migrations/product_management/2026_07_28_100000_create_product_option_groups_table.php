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
        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('product_option_id')->constrained('product_options')->onDelete('cascade');

            $table->string('name');
            $table->longText('message')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });

        Schema::create('product_option_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_group_id')->constrained('product_option_groups')->onDelete('cascade');
            $table->foreignId('product_option_item_id')->constrained('product_option_items')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_option_group_id', 'product_option_item_id'], 'option_group_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_group_items');
        Schema::dropIfExists('product_option_groups');
    }
};

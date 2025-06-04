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
        Schema::create('product_option_items', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('product_option_id')->constrained('product_options')->onDelete('cascade');

            $table->string('label');
            $table->decimal('daily', 10, 2)->nullable();
            $table->decimal('weekend', 10, 2)->nullable();
            $table->decimal('weekly', 10, 2)->nullable();
            $table->decimal('monthly', 10, 2)->nullable();
            $table->decimal('retail_price', 10, 2)->nullable();

            $table->enum('charged', ['Unlimited', '1 Time Max'])->default('Unlimited');
            $table->enum('value', ['Blank', 'Checked'])->default('Blank');
            $table->text('comment')->nullable();
            $table->string('accept_label')->nullable();
            $table->string('decline_label')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_items');
    }
};

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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('setting_type')->nullable(); // e.g., 'Email Settings', 'Product Settings'
            $table->string('setting_name')->unique(); // e.g., 'customer_email_send_email_address'
            $table->string('setting_title')->nullable(); // e.g., 'Customer Development/Staging Email'
            $table->string('value_type')->nullable(); // e.g., 'string', 'integer', 'boolean', 'value', 'content'
            $table->string('setting_value')->nullable(); // e.g., 'example@example.com'
            $table->json('setting_options')->nullable(); // For options like dropdowns
            $table->unsignedBigInteger('created_by')->nullable(); // User ID of the creator
            $table->unsignedBigInteger('updated_by')->nullable(); // User ID of the last updater
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

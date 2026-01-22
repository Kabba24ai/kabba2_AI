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
        Schema::create('sms_broadcasts', function (Blueprint $table) {
            $table->id();

              // Foreign Key to SMS Categories
            $table->unsignedBigInteger('sms_cat_id');
            $table->foreign('sms_cat_id')->references('id')->on('sms_categories')->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();

            $table->dateTime('send_date')->nullable();

            // Broadcast status: pending, created, sent
            $table->enum('status', ['pending', 'created', 'sent'])->default('created');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_broadcasts');
    }
};

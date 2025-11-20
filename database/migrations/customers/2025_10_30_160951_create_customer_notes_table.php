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
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->unsignedBigInteger('customer_id')->index(); // Related customer
            $table->unsignedBigInteger('created_by')->nullable()->index(); // Who added the note
            $table->text('description')->nullable(); // Note content
            $table->date('created_date')->nullable(); // Date part
            $table->time('created_time')->nullable(); // Time part

            $table->timestamps();

            // Foreign keys (optional but recommended)
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};

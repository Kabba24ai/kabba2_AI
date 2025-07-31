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
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique(); // Unique identifier for the note
            $table->unsignedBigInteger('order_id');
            $table->text('note');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->nullableMorphs('created_by'); // For both customer and user
            $table->nullableMorphs('updated_by'); // For both customer and user
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade')->onUpdate('cascade'); // Assuming you have an orders table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade'); // Assuming you have a users table
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notes');
    }
};

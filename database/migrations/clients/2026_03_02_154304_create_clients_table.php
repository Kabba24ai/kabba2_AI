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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique(); // Unique identifier for the client
            $table->string('name');
            $table->string('code')->unique();
            $table->string('api_url')->nullable()->comment('Base URL for the client API');
            $table->string('admin_url')->nullable()->comment('Base URL for the client admin panel');
            $table->string('front_url')->nullable()->comment('Base URL for the client front panel');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

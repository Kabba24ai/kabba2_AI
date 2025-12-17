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
        Schema::create('equipment_status_logs', function (Blueprint $table) {
            $table->id();

                // Relations
            $table->foreignId('equipment_id')
                ->constrained('equipment')
                ->cascadeOnDelete();

            // Status transition
            $table->string('from_status')->nullable(); 
            $table->string('to_status');

            // Who changed it
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // When it changed
            $table->timestamp('changed_at')->useCurrent();

            $table->timestamps();


            // Indexes for dashboard performance
            $table->index(['from_status', 'to_status']);
            $table->index('changed_at');

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_status_logs');
    }
};

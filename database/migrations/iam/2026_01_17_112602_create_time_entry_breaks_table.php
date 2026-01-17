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
        Schema::create('time_entry_breaks', function (Blueprint $table) {
            $table->id();

            
                $table->foreignId('time_entry_id')
                    ->constrained('time_entries')
                    ->cascadeOnDelete();

                $table->enum('type', ['lunch', 'other']);

                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();

            
                    $table->timestamps();

                    

                $table->index(['time_entry_id', 'type']);

                });
            }

            /**
             * Reverse the migrations.
             */
            public function down(): void
            {
                Schema::dropIfExists('time_entry_breaks');
    }
};

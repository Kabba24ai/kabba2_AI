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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
                $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();

            $table->unsignedInteger('break_duration')
                ->default(0)
                ->comment('Break duration in minutes');

            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'completed', 'edited'])
                ->default('active');

            /**
             * MySQL GENERATED column
             */
            // $table->decimal('total_hours', 5, 2)
            //     ->storedAs("
            //         CASE
            //             WHEN clock_out IS NOT NULL
            //             THEN ROUND(
            //                 (TIMESTAMPDIFF(SECOND, clock_in, clock_out)
            //                 - (break_duration * 60)) / 3600, 2
            //             )
            //             ELSE 0
            //         END
            //     ");

                $table->decimal('total_hours', 5, 2)->default(0); //  NORMAL COLUMN



            $table->timestamps();

            $table->index(['employee_id', 'clock_in']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};

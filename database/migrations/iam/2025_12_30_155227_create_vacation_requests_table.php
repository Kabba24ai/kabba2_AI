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
        Schema::create('vacation_requests', function (Blueprint $table) {
            $table->id();

                $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');

            // Hours requested (matches React UI)
            $table->decimal('hours', 5, 2);

            $table->enum('request_type', [
                'vacation',
                'sick',
                'personal',
                'unpaid'
            ])->default('vacation');

            $table->enum('status', [
                'pending',
                'approved',
                'denied',
                'cancelled'
            ])->default('pending');

            $table->text('notes')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->text('denial_reason')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('employee_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('request_type');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacation_requests');
    }
};

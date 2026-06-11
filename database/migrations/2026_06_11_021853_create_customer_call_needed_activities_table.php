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
        Schema::create('customer_call_needed_activities', function (Blueprint $table) {

            $table->id();

            $table->foreignId('customer_call_needed_id')
                ->constrained('customer_call_neededs')
                ->cascadeOnDelete();

            $table->string('status');

            $table->text('notes')->nullable();

            $table->date('follow_up_date')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('customer_call_needed_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_call_needed_activities');
    }
};

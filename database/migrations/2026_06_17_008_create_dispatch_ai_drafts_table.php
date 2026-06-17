<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_ai_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->date('draft_date');
            $table->unsignedTinyInteger('look_ahead_days')->default(3);
            $table->string('ai_model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('ai_reasoning')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->enum('triggered_by', ['cron', 'manual'])->default('manual');
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('triggered_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_ai_drafts');
    }
};

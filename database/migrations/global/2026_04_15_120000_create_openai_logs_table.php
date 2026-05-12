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
        Schema::create('openai_logs', function (Blueprint $table) {
            $table->id();

            // Request details
            $table->string('request_type'); // e.g., 'chat_completion', 'embedding', 'moderation'
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');

            // Request and response data
            $table->json('sent_data')->nullable(); // Data sent to OpenAI API
            $table->json('received_data')->nullable(); // Response from OpenAI API

            // Error handling
            $table->text('error_message')->nullable(); // Error message if request failed
            $table->string('error_code')->nullable(); // OpenAI error code

            // Usage tracking
            $table->integer('prompt_tokens')->nullable(); // Tokens used in prompt
            $table->integer('completion_tokens')->nullable(); // Tokens used in completion
            $table->integer('total_tokens')->nullable(); // Total tokens used

            // Performance metrics
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds

            // OpenAI specific
            $table->string('openai_request_id')->nullable(); // OpenAI request/transaction ID
            $table->string('model')->nullable(); // Model used (e.g., 'gpt-4', 'gpt-3.5-turbo')

            // Additional metadata
            $table->string('endpoint')->nullable(); // API endpoint used
            $table->string('ip_address')->nullable(); // Client IP (optional)
            $table->text('notes')->nullable(); // Additional notes or context

            $table->timestamps();

            // Indexes for better querying
            $table->index('status');
            $table->index('request_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openai_logs');
    }
};

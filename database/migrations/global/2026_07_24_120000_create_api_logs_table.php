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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();

            // Which whitelisted API this call belongs to (must match config('api_logging.services'))
            $table->string('service_name'); // e.g. 'kabba_client_api'
            $table->string('name'); // human readable action, e.g. 'Save Application Code'

            // Request details
            $table->string('method', 10); // GET, POST, etc.
            $table->string('endpoint');
            $table->json('request_params')->nullable();

            // Response details
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_json')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();

            // Timing
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->integer('duration_ms')->nullable();

            $table->timestamps();

            $table->index('service_name');
            $table->index('status');
            $table->index('requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};

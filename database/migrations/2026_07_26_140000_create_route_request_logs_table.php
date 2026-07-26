<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic log for the shared routing layer (App\Services\Routing). One row
 * per estimate attempt across ALL consumers (Field Service now, Dispatch
 * later). Deliberately privacy-safe: no API keys, no auth headers, no full
 * customer addresses, no raw provider payloads — only the consuming module,
 * provider, normalized status/HTTP code, timing, and non-sensitive provider
 * place references.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('consumer')->index();          // e.g. 'field_service'
            $table->string('provider');                    // e.g. 'google'
            $table->string('status')->index();             // App\Enums\Routing\RouteStatus value
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('origin_reference')->nullable();       // provider place id, not an address
            $table->string('destination_reference')->nullable();  // provider place id, not an address
            $table->string('notes')->nullable();           // short, non-sensitive
            $table->timestamps();

            $table->index(['consumer', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_request_logs');
    }
};

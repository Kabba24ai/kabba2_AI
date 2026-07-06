<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();

            $table->string('event_type');   // App\Enums\Service\ServiceTicketEventType
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Audit log — no soft deletes; events are never edited or removed.
            $table->timestamps();

            $table->index(['service_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_events');
    }
};

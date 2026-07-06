<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();

            // Physical storage goes through MediaHelper into the central
            // `media` table (Kabba convention); this row adds the
            // service-specific context and a denormalized snapshot.
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('media_type');   // App\Enums\Service\ServiceMediaType
            $table->string('category');     // App\Enums\Service\ServiceMediaCategory
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_ticket_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_media');
    }
};

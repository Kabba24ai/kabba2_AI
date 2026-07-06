<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();

            // Free-form part record — parts do NOT need to exist in the parts
            // module, and recording one never deducts inventory.
            $table->string('part_number')->nullable();
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('customer_price', 10, 2)->nullable();
            $table->boolean('warranty_eligible')->default(false);

            // Future linking (e.g. converted into a charge line, or matched to
            // an inventory part) — prevents double billing later.
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('service_ticket_id');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_parts');
    }
};

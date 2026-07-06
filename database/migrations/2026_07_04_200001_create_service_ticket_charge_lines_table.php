<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_charge_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();

            $table->string('charge_type'); // App\Enums\Service\ServiceChargeType
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_amount', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->boolean('taxable')->default(false);
            $table->boolean('billable')->default(true);

            // Optional pointer back to whatever produced this line (e.g. a labor
            // entry rolled up into a labor charge) — no FK, polymorphic reference.
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_ticket_id', 'billable']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_charge_lines');
    }
};

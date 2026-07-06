<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_ticket_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();

            // Handoff references — the Financial Engine consumes these.
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('order_extra_charge_id')->nullable()->constrained('order_extra_charges')->nullOnDelete();

            // One active settlement per ticket; 'reversed' reserved for a
            // future authorized reopen workflow.
            $table->string('status')->default('created');

            // Calculated totals — never user-edited; math lives in SettlementPackage.
            $table->decimal('labor_total', 10, 2)->default(0);
            $table->decimal('parts_total', 10, 2)->default(0);
            $table->decimal('other_total', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('credits_total', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2)->default(0);

            // Full line-item snapshot at handoff time (audit record).
            $table->json('package');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_settlements');
    }
};

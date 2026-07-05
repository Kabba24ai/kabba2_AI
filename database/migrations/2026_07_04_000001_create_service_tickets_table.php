<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            // Nullable so the model's created-hook can backfill SVC-##### from
            // the row id; unique still enforced for real values.
            $table->string('ticket_number')->nullable()->unique();
            $table->string('service_type');            // App\Enums\Service\ServiceType
            $table->string('service_location');        // App\Enums\Service\ServiceLocation
            $table->string('priority');                // App\Enums\Service\ServicePriority
            $table->string('repair_status');           // App\Enums\Service\RepairStatus
            $table->string('financial_responsibility');// App\Enums\Service\FinancialResponsibility
            $table->string('financial_status');        // App\Enums\Service\FinancialStatus
            $table->text('description')->nullable();

            // Order association: reference only — the Order module remains the
            // source of truth for agreements, checklists, media, and payments.
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('rental_date')->nullable();

            $table->string('blocked_reason')->nullable();
            $table->date('expected_action_date')->nullable();

            $table->timestamp('opened_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['repair_status', 'priority']);
            $table->index('expected_action_date');
            $table->index('financial_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tickets');
    }
};

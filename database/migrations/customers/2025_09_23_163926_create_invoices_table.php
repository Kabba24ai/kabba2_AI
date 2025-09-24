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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_created_by')->constrained('users')->cascadeOnDelete();


            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('sales_tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('invoice_notes')->nullable();

            $table->enum('invoice_status', ['paid', 'overdue', 'pending'])->default('pending');
            $table->enum('is_email_send', ['send', 'unsend'])->default('unsend');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

<?php

use App\Enums\Orders\PodPaymentLinkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pod_payment_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                  ->unique()
                  ->constrained('orders')
                  ->onDelete('cascade');

            // The token embedded in the payment URL (replaces encrypt(order->unique_id) for tracking)
            $table->string('payment_link_token')->unique();

            // Current lifecycle status
            $table->string('pod_status')->default(PodPaymentLinkStatus::Pending->value);

            // ── Lifecycle timestamps ────────────────────────────────────────
            $table->timestamp('payment_link_created_at')->nullable();
            $table->timestamp('payment_link_opened_at')->nullable();   // first open
            $table->unsignedInteger('payment_link_open_count')->default(0);

            $table->timestamp('pod_reminder_1_sent_at')->nullable();
            $table->timestamp('pod_reminder_2_sent_at')->nullable();
            $table->timestamp('pod_reminder_3_sent_at')->nullable();
            $table->timestamp('pod_reminder_4_sent_at')->nullable();

            $table->timestamp('pod_expired_at')->nullable();
            $table->timestamp('pod_reactivated_at')->nullable();
            $table->timestamp('pod_payment_completed_at')->nullable();

            $table->timestamps();

            $table->index('pod_status');
            $table->index('payment_link_created_at');
            $table->index('pod_payment_completed_at');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pod_payment_links');
    }
};

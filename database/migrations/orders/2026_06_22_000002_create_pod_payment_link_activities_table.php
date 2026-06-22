<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pod_payment_link_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pod_payment_link_id')
                  ->constrained('pod_payment_links')
                  ->onDelete('cascade');

            // Denormalised for direct querying without a join
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade');

            // Event type (see PodPaymentLinkEvent enum)
            $table->string('event')->index();

            // Flexible bag: IP, user agent, reminder number, payment amount, notes, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['pod_payment_link_id', 'event']);
            $table->index(['order_id', 'event']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pod_payment_link_activities');
    }
};

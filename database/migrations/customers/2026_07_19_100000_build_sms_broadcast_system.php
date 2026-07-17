<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM SMS rebuild — separates reusable content from actual sends.
 *
 * - sms_broadcasts remains the reusable MESSAGE LIBRARY (name, category,
 *   content). Its legacy status/send_date columns are left untouched as
 *   historical data; the new UI ignores them.
 * - sms_audiences stores reusable segmentation RULES (never customer lists).
 * - sms_broadcast_events are the actual broadcasts: each freezes its own
 *   message snapshot, audience-rule snapshot, and recipient package.
 * - sms_broadcast_recipients is the frozen per-recipient package the
 *   transmission worker processes — never rebuilt during sending.
 *
 * Everything is additive; no existing rows are modified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_broadcasts', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status');
            $table->foreignId('created_by')->nullable()->after('archived_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('sms_audiences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // true = base audience is All Eligible CRM Recipients
            $table->boolean('base_all')->default(false);
            $table->string('positive_mode', 10)->nullable(); // any | all (null when base_all)
            $table->json('include_tag_ids')->nullable();
            $table->json('exclude_tag_ids')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sms_broadcast_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Source library message — nullable so deleting the library row
            // never corrupts history (snapshot columns stand alone)
            $table->foreignId('sms_broadcast_id')->nullable()
                ->constrained('sms_broadcasts')->nullOnDelete();
            $table->foreignId('sms_audience_id')->nullable()
                ->constrained('sms_audiences')->nullOnDelete();

            // ── Frozen message snapshot ──
            $table->string('message_name')->nullable();
            $table->text('message_content')->nullable();
            $table->string('category_name')->nullable();

            // ── Frozen audience-rule snapshot ──
            $table->string('audience_type', 20)->default('tags'); // all | tags | saved
            $table->string('positive_mode', 10)->nullable();      // any | all
            $table->json('include_tag_ids')->nullable();
            $table->json('include_tag_names')->nullable();
            $table->json('exclude_tag_ids')->nullable();
            $table->json('exclude_tag_names')->nullable();
            $table->string('audience_description')->nullable();

            // ── Package + lifecycle ──
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 30)->default('draft');
            $table->unsignedTinyInteger('wizard_step')->default(1);
            $table->dateTime('queued_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sending_started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'archived_at']);
            $table->index('scheduled_at');
        });

        Schema::create('sms_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_broadcast_event_id')
                ->constrained('sms_broadcast_events')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()
                ->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('phone', 32); // normalized E.164, frozen
            $table->string('status', 10)->default('pending'); // pending | sent | failed
            $table->string('twilio_sid')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['sms_broadcast_event_id', 'phone'], 'sms_bro_event_phone_unique');
            $table->index(['sms_broadcast_event_id', 'status'], 'sms_bro_event_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_broadcast_recipients');
        Schema::dropIfExists('sms_broadcast_events');
        Schema::dropIfExists('sms_audiences');
        Schema::table('sms_broadcasts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('archived_at');
        });
    }
};

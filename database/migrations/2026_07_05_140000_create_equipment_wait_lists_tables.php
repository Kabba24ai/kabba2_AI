<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One record = one equipment need. No quantity support by design —
        // a customer needing three machines gets three records.
        Schema::create('equipment_wait_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // Display snapshot pulled from CRM at creation (record survives
            // later CRM edits); the live relation is still the source of truth.
            $table->string('customer_name');
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('request_type');                 // App\Enums\WaitList\WaitListRequestType
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();

            $table->string('store_preference');             // App\Enums\WaitList\WaitListStorePreference
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->text('reason')->nullable();
            $table->text('internal_notes')->nullable();

            $table->string('status')->default('active');    // App\Enums\WaitList\WaitListStatus
            $table->unsignedSmallInteger('priority_override')->nullable(); // manual only; higher = more urgent

            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'request_type']);
            $table->index('product_category_id');
        });

        // Specific-equipment requests: up to 3 equipment IDs (validated, not schema-enforced)
        Schema::create('equipment_wait_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_wait_list_id')
                ->constrained(table: 'equipment_wait_lists', indexName: 'wl_item_wait_list_fk')
                ->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['equipment_wait_list_id', 'equipment_id'], 'wait_list_equipment_unique');
        });

        // Manual communication history — every entry: employee, timestamp, type, note
        Schema::create('equipment_wait_list_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_wait_list_id')
                ->constrained(table: 'equipment_wait_lists', indexName: 'wl_comm_wait_list_fk')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');                          // App\Enums\WaitList\WaitListCommunicationType
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Match alerts fired on return/check-in; alerts stay viewable forever
        Schema::create('equipment_wait_list_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_wait_list_id')
                ->constrained(table: 'equipment_wait_lists', indexName: 'wl_alert_wait_list_fk')
                ->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();

            $table->string('match_type');                    // exact_equipment | category
            $table->foreignId('matched_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('equipment_status_at_match')->nullable();

            $table->string('status')->default('unacknowledged'); // unacknowledged | acknowledged | dismissed
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();

            // Push tracking: first push may be deferred to business hours;
            // second push fires 30 min after the first if unacknowledged.
            $table->timestamp('first_push_sent_at')->nullable();
            $table->timestamp('second_push_sent_at')->nullable();
            $table->timestamp('push_deferred_until')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_wait_list_alerts');
        Schema::dropIfExists('equipment_wait_list_communications');
        Schema::dropIfExists('equipment_wait_list_items');
        Schema::dropIfExists('equipment_wait_lists');
    }
};

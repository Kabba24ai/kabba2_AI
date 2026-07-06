<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            // Approval workflow (Phase 2E)
            $table->string('approval_status')->default('not_required')->after('responsibility_decided_by'); // App\Enums\Service\ApprovalStatus
            $table->string('approval_type')->nullable()->after('approval_status');                          // App\Enums\Service\ApprovalType
            $table->timestamp('estimate_sent_at')->nullable()->after('approval_type');
            $table->timestamp('estimate_approved_at')->nullable()->after('estimate_sent_at');
            $table->timestamp('estimate_declined_at')->nullable()->after('estimate_approved_at');
            $table->string('approved_by_customer_name')->nullable()->after('estimate_declined_at');
            $table->foreignId('approved_by_user_id')->nullable()->after('approved_by_customer_name')
                ->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable()->after('approved_by_user_id');

            // Repair authorization gate
            $table->boolean('repair_authorized')->default(false)->after('approval_notes');
            $table->timestamp('repair_authorized_at')->nullable()->after('repair_authorized');
            $table->foreignId('repair_authorized_by')->nullable()->after('repair_authorized_at')
                ->constrained('users')->nullOnDelete();
            $table->text('repair_authorization_notes')->nullable()->after('repair_authorized_by');

            // Parts deposit / repair funding — state tracking only, no payments.
            $table->boolean('parts_deposit_required')->default(false)->after('repair_authorization_notes');
            $table->decimal('parts_deposit_amount', 10, 2)->nullable()->after('parts_deposit_required');
            $table->boolean('parts_deposit_paid')->default(false)->after('parts_deposit_amount');
            $table->timestamp('parts_deposit_paid_at')->nullable()->after('parts_deposit_paid');
            $table->string('parts_deposit_payment_reference')->nullable()->after('parts_deposit_paid_at');
            $table->boolean('parts_deposit_creditable')->default(false)->after('parts_deposit_payment_reference');
            $table->boolean('parts_deposit_applied_to_final_invoice')->default(false)->after('parts_deposit_creditable');
            $table->boolean('deposit_override')->default(false)->after('parts_deposit_applied_to_final_invoice');
            $table->string('deposit_override_reason')->nullable()->after('deposit_override');
            $table->foreignId('deposit_override_by')->nullable()->after('deposit_override_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('deposit_override_at')->nullable()->after('deposit_override_by');

            $table->index('approval_status');
            $table->index('repair_authorized');
        });
    }

    public function down(): void
    {
        Schema::table('service_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropConstrainedForeignId('repair_authorized_by');
            $table->dropConstrainedForeignId('deposit_override_by');
            $table->dropIndex(['approval_status']);
            $table->dropIndex(['repair_authorized']);
            $table->dropColumn([
                'approval_status', 'approval_type', 'estimate_sent_at', 'estimate_approved_at',
                'estimate_declined_at', 'approved_by_customer_name', 'approval_notes',
                'repair_authorized', 'repair_authorized_at', 'repair_authorization_notes',
                'parts_deposit_required', 'parts_deposit_amount', 'parts_deposit_paid',
                'parts_deposit_paid_at', 'parts_deposit_payment_reference', 'parts_deposit_creditable',
                'parts_deposit_applied_to_final_invoice', 'deposit_override', 'deposit_override_reason',
                'deposit_override_at',
            ]);
        });
    }
};

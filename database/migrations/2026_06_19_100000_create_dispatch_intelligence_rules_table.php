<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_intelligence_rules', function (Blueprint $table) {
            $table->id();

            $table->enum('rule_type', [
                'driver_assignment',
                'routing',
                'scheduling',
                'loading',
                'customer_preference',
                'priority_override',
                'seasonal',
                'geographic',
                'safety',
                'operational',
            ]);

            $table->string('rule_name');
            $table->text('condition');
            $table->text('recommendation');
            $table->text('reason');

            $table->unsignedTinyInteger('priority')->default(50);
            $table->decimal('confidence_score', 3, 2)->default(0.70);

            $table->json('tags')->nullable();

            $table->enum('source_type', [
                'admin',
                'ai_generated',
                'experience',
                'customer_specific',
                'driver_feedback',
            ])->default('admin');

            $table->boolean('approved_by_admin')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reviewed_at')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['rule_type', 'is_active'], 'dir_type_active_idx');
            $table->index(['approved_by_admin', 'is_active'], 'dir_approved_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_intelligence_rules');
    }
};

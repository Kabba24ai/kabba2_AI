<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_intelligence_rules', function (Blueprint $table) {
            $table->id();

            // Scope: category is always required; profile is optional (null = applies to whole category)
            $table->foreignId('equipment_category_id')
                ->constrained('product_categories')
                ->cascadeOnDelete();

            $table->foreignId('equipment_profile_id')
                ->nullable()
                ->constrained('equipment_ai_profiles')
                ->nullOnDelete();

            // Rule classification
            $table->enum('rule_type', [
                'suitability',
                'limitation',
                'substitution',
                'scheduling',
                'safety',
                'delivery',
                'productivity',
                'terrain',
                'customer_preference',
                'application_use_case',
            ]);

            $table->string('rule_name');
            $table->text('condition');        // When this rule fires
            $table->text('recommendation');   // What to do/recommend
            $table->text('reason');           // Why

            // Scoring
            $table->unsignedTinyInteger('priority')->default(50);       // 1–100
            $table->decimal('confidence_score', 3, 2)->default(0.70);   // 0.00–1.00

            // Tags: residential, commercial, soft_ground, etc.
            $table->json('tags')->nullable();

            // Source and approval
            $table->enum('source_type', [
                'admin',
                'ai_generated',
                'manufacturer',
                'dealer',
                'internal_experience',
            ])->default('admin');

            $table->boolean('approved_by_admin')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Lifecycle
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reviewed_at')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            // Common query patterns
            $table->index(['equipment_category_id', 'rule_type', 'is_active']);
            $table->index(['equipment_profile_id', 'is_active']);
            $table->index(['approved_by_admin', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_intelligence_rules');
    }
};

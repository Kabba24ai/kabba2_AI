<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_substitution_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_product_id')->nullable()->constrained('order_products')->nullOnDelete();
            $table->foreignId('original_equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('substitute_equipment_id')->constrained('equipment')->cascadeOnDelete();

            // Composite score (0–100)
            $table->decimal('overall_score', 5, 2);
            $table->decimal('spec_score', 5, 2);
            $table->decimal('rule_score', 5, 2);
            $table->decimal('compatibility_score', 5, 2);

            $table->boolean('requires_manager_approval')->default(false);
            $table->string('approval_reason')->nullable();

            // Structured audit: which rules fired and how
            $table->json('rule_violations')->nullable();   // [{rule_id, rule_name, severity, detail}]
            $table->json('rule_supports')->nullable();     // [{rule_id, rule_name, detail}]
            $table->json('spec_comparison')->nullable();   // [{spec_key, original_val, substitute_val, delta}]
            $table->json('evaluation_context')->nullable(); // job_tags, customer_type, etc.

            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['original_equipment_id', 'substitute_equipment_id']);
            $table->index('order_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_substitution_logs');
    }
};

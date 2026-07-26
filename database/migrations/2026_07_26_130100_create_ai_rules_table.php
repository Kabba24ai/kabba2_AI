<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical AI Rules Repository — the governed rulebook that employees and
 * future AI integrations refer to. Mirrors the house `dispatch_intelligence_rules`
 * governance pattern (approval trio + created/updated_by + softDeletes) and adds
 * the authority-boundary and machine-readable fields this domain requires.
 *
 * ONE canonical record drives both the human CRM display and the future AI
 * instruction, so the two can never drift. Editing an approved rule resets its
 * approval and bumps the version (enforced in the controller), and change_log
 * keeps an append-only who/when history without a separate table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_rules', function (Blueprint $table) {
            $table->id();

            $table->string('rule_key')->unique();       // stable machine key
            $table->string('rule_name');
            $table->string('business_area')->nullable();
            $table->text('purpose')->nullable();

            // Deterministic vs AI, plain-language + machine-readable.
            $table->text('trigger_description')->nullable();
            $table->json('evaluable_inputs')->nullable();   // facts the AI may evaluate (future-eligible)
            $table->text('deterministic_action')->nullable();
            $table->text('ai_assessment_instruction')->nullable();
            $table->json('ai_may_recommend')->nullable();
            $table->json('ai_may_execute')->nullable();      // default empty — execution is separately granted
            $table->json('ai_must_not')->nullable();

            $table->string('required_human_reviewer')->nullable();
            $table->string('escalation_destination')->nullable();

            // Authority boundary + lifecycle.
            $table->string('authority')->default('recommend_only');            // AiRuleAuthority
            $table->string('implementation_status')->default('defined_not_automated'); // AiRuleImplementationStatus
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->date('effective_date')->nullable();

            // Append-only change history (who/when/what) — anti-drift.
            $table->json('change_log')->nullable();

            // Governance trio (house convention).
            $table->boolean('approved_by_admin')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'implementation_status'], 'ai_rules_active_status_idx');
            $table->index('business_area', 'ai_rules_area_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_rules');
    }
};

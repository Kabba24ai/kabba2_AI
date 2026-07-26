<?php

namespace App\Models\Ai;

use App\Enums\AiRules\AiRuleAuthority;
use App\Enums\AiRules\AiRuleImplementationStatus;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single governed AI rule. One canonical record backs both the human-readable
 * CRM presentation and the future machine/AI consumption, so they cannot drift.
 *
 * Ownership & future AI Hub compatibility: this table is the CRM-owned
 * authoritative repository (module = "crm"). A future Kabba AI Hub is an
 * orchestration layer (Zapier-like) that will REFERENCE rules by stable
 * identity — {module, rule_key, version, authority, implementation_status} —
 * and must never duplicate, redefine, or grant authority beyond what the source
 * rule declares. CRM remains authoritative for definition, approval, version,
 * and the authority boundary. `module` is implicit today (this repository);
 * no hub is built in this phase and no synchronization exists.
 */
class AiRule extends Model
{
    use SoftDeletes;

    protected $table = 'ai_rules';

    protected $fillable = [
        'rule_key',
        'rule_name',
        'business_area',
        'purpose',
        'trigger_description',
        'evaluable_inputs',
        'deterministic_action',
        'ai_assessment_instruction',
        'ai_may_recommend',
        'ai_may_execute',
        'ai_must_not',
        'required_human_reviewer',
        'escalation_destination',
        'authority',
        'implementation_status',
        'is_active',
        'version',
        'effective_date',
        'change_log',
        'approved_by_admin',
        'approved_by',
        'approved_at',
        'last_reviewed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'evaluable_inputs'      => 'array',
        'ai_may_recommend'      => 'array',
        'ai_may_execute'        => 'array',
        'ai_must_not'           => 'array',
        'change_log'            => 'array',
        'authority'             => AiRuleAuthority::class,
        'implementation_status' => AiRuleImplementationStatus::class,
        'is_active'             => 'boolean',
        'approved_by_admin'     => 'boolean',
        'version'               => 'integer',
        'effective_date'        => 'date',
        'approved_at'           => 'datetime',
        'last_reviewed_at'      => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved_by_admin', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approved_by_admin', false);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return ! $this->approved_by_admin;
    }

    public function mayExecute(): bool
    {
        return $this->authority === AiRuleAuthority::MayExecute;
    }

    /** Append a who/when entry to the anti-drift change log. */
    public function pushChangeLog(string $action, ?int $userId): void
    {
        $log = $this->change_log ?? [];
        $log[] = [
            'action'  => $action,
            'user_id' => $userId,
            'version' => $this->version,
            'at'      => now()->toIso8601String(),
        ];
        $this->change_log = $log;
    }
}

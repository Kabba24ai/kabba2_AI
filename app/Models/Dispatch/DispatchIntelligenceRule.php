<?php

namespace App\Models\Dispatch;

use App\Enums\Dispatch\DispatchIntelligenceRuleSource;
use App\Enums\Dispatch\DispatchIntelligenceRuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchIntelligenceRule extends Model
{
    use SoftDeletes;

    protected $table = 'dispatch_intelligence_rules';

    protected $fillable = [
        'rule_type',
        'rule_name',
        'condition',
        'recommendation',
        'reason',
        'priority',
        'confidence_score',
        'tags',
        'source_type',
        'approved_by_admin',
        'approved_by',
        'approved_at',
        'is_active',
        'last_reviewed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rule_type'         => DispatchIntelligenceRuleType::class,
        'source_type'       => DispatchIntelligenceRuleSource::class,
        'tags'              => 'array',
        'approved_by_admin' => 'boolean',
        'is_active'         => 'boolean',
        'confidence_score'  => 'float',
        'approved_at'       => 'datetime',
        'last_reviewed_at'  => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function approver()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\Iam\Personnel\User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved_by_admin', true);
    }

    public function scopePending($query)
    {
        return $query->where('approved_by_admin', false);
    }

    public function scopeOfType($query, DispatchIntelligenceRuleType|string $type)
    {
        $value = $type instanceof DispatchIntelligenceRuleType ? $type->value : $type;
        return $query->where('rule_type', $value);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return !$this->approved_by_admin;
    }

    public function confidencePercent(): int
    {
        return (int) round($this->confidence_score * 100);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }
}

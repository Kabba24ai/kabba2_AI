<?php

namespace App\Models\MaintenanceManagement;

use App\Enums\EquipmentAi\IntelligenceRuleSource;
use App\Enums\EquipmentAi\IntelligenceRuleType;
use App\Models\Iam\Personnel\User;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentIntelligenceRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'equipment_category_id',
        'equipment_profile_id',
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
        'rule_type'          => IntelligenceRuleType::class,
        'source_type'        => IntelligenceRuleSource::class,
        'tags'               => 'array',
        'approved_by_admin'  => 'boolean',
        'is_active'          => 'boolean',
        'confidence_score'   => 'float',
        'approved_at'        => 'datetime',
        'last_reviewed_at'   => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'equipment_category_id');
    }

    public function profile()
    {
        return $this->belongsTo(EquipmentAiProfile::class, 'equipment_profile_id');
    }

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

    // ── Scopes ────────────────────────────────────────────────────────

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

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('equipment_category_id', $categoryId);
    }

    public function scopeForProfile($query, ?int $profileId)
    {
        return $query->where('equipment_profile_id', $profileId);
    }

    // Category-level rules (no specific profile) OR profile-specific
    public function scopeApplicableTo($query, int $categoryId, ?int $profileId = null)
    {
        return $query->where('equipment_category_id', $categoryId)
            ->where(function ($q) use ($profileId) {
                $q->whereNull('equipment_profile_id');
                if ($profileId) {
                    $q->orWhere('equipment_profile_id', $profileId);
                }
            });
    }

    public function scopeOfType($query, string $ruleType)
    {
        return $query->where('rule_type', $ruleType);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return !$this->approved_by_admin;
    }

    public function isProfileSpecific(): bool
    {
        return $this->equipment_profile_id !== null;
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

<?php

namespace App\Models\Service;

use App\Enums\Service\ApprovalType;
use App\Enums\Service\FinancialResponsibility;
use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Business-managed master data for the Service Ticket Responsibility stage —
 * the single source of truth for the decision options, their financial-path
 * mapping, and approval routing. Replaces the hard-coded
 * App\Enums\Service\ResponsibilityDecision.
 *
 * Guiding principle: a Responsibility Decision describes the OPERATIONAL
 * outcome; its financial_path (App\Enums\Service\FinancialResponsibility)
 * describes the FINANCIAL disposition. Related, but not the same — kept
 * separate so reporting can evolve (warranty performance, damage-waiver
 * profitability, internal quality cost) without redesign.
 *
 * ENFORCED this phase: financial_path, approval_type. The remaining behavior
 * flags are stored, documented configuration only — reserved for a later
 * workflow phase.
 */
class ServiceResponsibilityDecision extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'color',
        'financial_path',
        'approval_type',
        'financial_reporting_category',
        'allows_repair',
        'requires_diagnostic_fee',
        'is_terminal_resolution',
        'requires_customer_authorization',
        'requires_oem_authorization',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allows_repair'                   => 'boolean',
        'requires_diagnostic_fee'         => 'boolean',
        'is_terminal_resolution'          => 'boolean',
        'requires_customer_authorization' => 'boolean',
        'requires_oem_authorization'      => 'boolean',
        // Not fillable — set only by the seed migration, so admins can never
        // promote/demote a record's canonical status through the CRUD.
        'is_system'                       => 'boolean',
        'is_active'                       => 'boolean',
        'sort_order'                      => 'integer',
    ];

    /** Active decisions only — the set offered for a NEW selection. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Canonical display order: sort_order, then name as an alphabetical tie-break. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** The financial disposition this decision maps onto (null = no payer path). */
    public function financialResponsibility(): ?FinancialResponsibility
    {
        return $this->financial_path ? FinancialResponsibility::tryFrom($this->financial_path) : null;
    }

    /** The approval path this decision requires (null = no approval required). */
    public function approvalTypeEnum(): ?ApprovalType
    {
        return $this->approval_type ? ApprovalType::tryFrom($this->approval_type) : null;
    }

    public function tickets()
    {
        return $this->hasMany(ServiceTicket::class, 'responsibility_decision_id');
    }

    /** A decision referenced by any ticket may be deactivated but never deleted. */
    public function isInUse(): bool
    {
        return $this->tickets()->exists();
    }

    /**
     * Only custom, unused decisions may be physically deleted. Canonical
     * seeded concepts (is_system) are permanent — they may be renamed,
     * recolored, reordered, or deactivated, but never destroyed, so long-term
     * reporting stays stable.
     */
    public function isDeletable(): bool
    {
        return !$this->is_system && !$this->isInUse();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

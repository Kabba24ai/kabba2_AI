<?php

namespace App\Services\EquipmentAi;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use App\Models\MaintenanceManagement\EquipmentSubstitutionLog;
use App\Models\Orders\OrderProduct;

class SubstitutionScoringService
{
    // Scoring weights must sum to 100
    private const WEIGHT_SPEC          = 40;
    private const WEIGHT_RULE          = 35;
    private const WEIGHT_COMPATIBILITY = 25;

    // Approval thresholds
    private const APPROVAL_NOT_REQUIRED  = 80;
    private const APPROVAL_RECOMMENDED   = 60;
    // Below APPROVAL_RECOMMENDED → approval required

    /**
     * Evaluate a substitute for a given original equipment unit.
     *
     * $context = ['tags' => ['residential', 'soft_ground'], 'job_type' => 'tree_service', ...]
     */
    public function evaluate(
        Equipment $original,
        Equipment $substitute,
        array     $context = [],
        ?OrderProduct $orderProduct = null
    ): array {
        $originalProfile  = $this->findProfile($original);
        $substituteProfile = $this->findProfile($substitute);

        $categoryId = $original->product_category_id;

        // ── 1. Spec Score ────────────────────────────────────────────
        $specResult = $this->scoreSpecs($categoryId, $originalProfile, $substituteProfile);

        // ── 2. Rule Score ────────────────────────────────────────────
        $ruleResult = $this->scoreRules($categoryId, $substituteProfile, $context);

        // ── 3. Compatibility Score ───────────────────────────────────
        $compatResult = $this->scoreCompatibility($original, $substitute, $originalProfile, $substituteProfile);

        // ── 4. Composite ─────────────────────────────────────────────
        $overall = round(
            ($specResult['score']   * self::WEIGHT_SPEC / 100) +
            ($ruleResult['score']   * self::WEIGHT_RULE / 100) +
            ($compatResult['score'] * self::WEIGHT_COMPATIBILITY / 100),
            2
        );

        $approvalLevel  = $this->approvalLevel($overall, $ruleResult['has_critical_violation']);
        $approvalReason = $this->approvalReason($approvalLevel, $ruleResult['violations'], $specResult['misses']);

        $result = [
            'overall_score'             => $overall,
            'spec_score'                => $specResult['score'],
            'rule_score'                => $ruleResult['score'],
            'compatibility_score'       => $compatResult['score'],
            'requires_manager_approval' => $approvalLevel !== 'none',
            'approval_level'            => $approvalLevel,
            'approval_reason'           => $approvalReason,
            'rule_violations'           => $ruleResult['violations'],
            'rule_supports'             => $ruleResult['supports'],
            'spec_comparison'           => $specResult['comparison'],
            'spec_misses'               => $specResult['misses'],
            'compatibility_notes'       => $compatResult['notes'],
            'explanation'               => $this->buildExplanation($original, $substitute, $overall, $specResult, $ruleResult, $compatResult, $approvalLevel),
        ];

        // Persist audit log
        $log = EquipmentSubstitutionLog::create([
            'order_product_id'          => $orderProduct?->id,
            'original_equipment_id'     => $original->id,
            'substitute_equipment_id'   => $substitute->id,
            'overall_score'             => $overall,
            'spec_score'                => $specResult['score'],
            'rule_score'                => $ruleResult['score'],
            'compatibility_score'       => $compatResult['score'],
            'requires_manager_approval' => $result['requires_manager_approval'],
            'approval_reason'           => $approvalReason,
            'rule_violations'           => $ruleResult['violations'],
            'rule_supports'             => $ruleResult['supports'],
            'spec_comparison'           => $specResult['comparison'],
            'evaluation_context'        => $context,
            'evaluated_by'              => auth()->id(),
        ]);

        $result['log_id'] = $log->id;

        return $result;
    }

    // ── Spec Scoring ──────────────────────────────────────────────────

    private function scoreSpecs(int $categoryId, ?EquipmentAiProfile $original, ?EquipmentAiProfile $substitute): array
    {
        if (!$original || !$substitute) {
            return ['score' => 50, 'comparison' => [], 'misses' => ['No AI profile found — spec comparison unavailable.']];
        }

        $compKeys   = EquipmentCategoryComparisonKey::where('category_id', $categoryId)->get();
        $origSpecs  = $original->specifications()->whereIn('spec_key', $compKeys->pluck('spec_key'))->get()->keyBy('spec_key');
        $subSpecs   = $substitute->specifications()->whereIn('spec_key', $compKeys->pluck('spec_key'))->get()->keyBy('spec_key');

        $comparison = [];
        $misses     = [];
        $metCount   = 0;
        $total      = 0;

        foreach ($compKeys as $key) {
            $origSpec = $origSpecs->get($key->spec_key);
            $subSpec  = $subSpecs->get($key->spec_key);

            if (!$origSpec || !$origSpec->spec_value) continue;

            $total++;
            $origVal = (float) filter_var($origSpec->spec_value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $subVal  = $subSpec ? (float) filter_var($subSpec->spec_value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;

            $meets = false;
            $delta = null;

            if ($subVal !== null && $origVal > 0) {
                $delta = $subVal - $origVal;
                $deltaPercent = round(($delta / $origVal) * 100, 1);

                // "Meets or exceeds" for criteria where bigger = better (height, reach, capacity)
                $meets = $key->upgrade_exceeds_value
                    ? $subVal >= $origVal
                    : ($key->upgrade_is_below_value ? $subVal <= $origVal : $subVal >= ($origVal * 0.90));

                if ($meets) {
                    $metCount++;
                } else {
                    $misses[] = "{$key->spec_label}: original {$origSpec->spec_value}{$origSpec->spec_unit}, substitute " . ($subSpec?->spec_value ?? 'N/A') . ($subSpec?->spec_unit ?? '') . " (−{$deltaPercent}%)";
                }

                $comparison[] = [
                    'spec_key'      => $key->spec_key,
                    'spec_label'    => $key->spec_label,
                    'original_val'  => $origSpec->spec_value . ($origSpec->spec_unit ? " {$origSpec->spec_unit}" : ''),
                    'sub_val'       => ($subSpec?->spec_value ?? '—') . ($subSpec?->spec_unit ? " {$subSpec->spec_unit}" : ''),
                    'delta_percent' => $deltaPercent ?? null,
                    'meets'         => $meets,
                ];
            } else {
                $comparison[] = [
                    'spec_key'      => $key->spec_key,
                    'spec_label'    => $key->spec_label,
                    'original_val'  => $origSpec->spec_value . ($origSpec->spec_unit ? " {$origSpec->spec_unit}" : ''),
                    'sub_val'       => '—',
                    'delta_percent' => null,
                    'meets'         => false,
                ];
                $misses[] = "{$key->spec_label}: not available on substitute";
            }
        }

        $score = $total > 0 ? round(($metCount / $total) * 100) : 50;

        return ['score' => $score, 'comparison' => $comparison, 'misses' => $misses];
    }

    // ── Rule Scoring ──────────────────────────────────────────────────

    private function scoreRules(int $categoryId, ?EquipmentAiProfile $substituteProfile, array $context): array
    {
        $profileId = $substituteProfile?->id;

        $rules = EquipmentIntelligenceRule::active()
            ->approved()
            ->applicableTo($categoryId, $profileId)
            ->ofType('substitution')
            ->orWhere(fn ($q) => $q
                ->active()->approved()
                ->applicableTo($categoryId, $profileId)
                ->ofType('limitation'))
            ->orWhere(fn ($q) => $q
                ->active()->approved()
                ->applicableTo($categoryId, $profileId)
                ->ofType('suitability'))
            ->orderByDesc('priority')
            ->get();

        $contextTags = $context['tags'] ?? [];
        $violations  = [];
        $supports    = [];
        $penalty     = 0;
        $hasCritical = false;

        foreach ($rules as $rule) {
            $ruleTags = $rule->tags ?? [];

            // Check if this rule is relevant to the context
            $tagMatch = empty($ruleTags) || !empty(array_intersect($ruleTags, $contextTags));
            if (!$tagMatch) continue;

            if ($rule->rule_type->value === 'limitation') {
                // Limitations reduce the score proportionally to priority
                $penaltyAmount = round(($rule->priority / 100) * 20); // max 20pt penalty per limitation
                $penalty += $penaltyAmount;

                if ($rule->priority >= 90) {
                    $hasCritical = true;
                    $penalty += 20; // extra for critical violations
                }

                $violations[] = [
                    'rule_id'   => $rule->id,
                    'rule_name' => $rule->rule_name,
                    'severity'  => $rule->priority >= 90 ? 'critical' : ($rule->priority >= 70 ? 'high' : 'medium'),
                    'detail'    => $rule->recommendation,
                    'reason'    => $rule->reason,
                ];
            } elseif (in_array($rule->rule_type->value, ['suitability', 'substitution'])) {
                $supports[] = [
                    'rule_id'   => $rule->id,
                    'rule_name' => $rule->rule_name,
                    'detail'    => $rule->recommendation,
                ];
            }
        }

        $score = max(0, 100 - $penalty);

        return [
            'score'                  => $score,
            'violations'             => $violations,
            'supports'               => $supports,
            'has_critical_violation' => $hasCritical,
        ];
    }

    // ── Compatibility Scoring ─────────────────────────────────────────

    private function scoreCompatibility(
        Equipment $original,
        Equipment $substitute,
        ?EquipmentAiProfile $origProfile,
        ?EquipmentAiProfile $subProfile
    ): array {
        $score = 100;
        $notes = [];

        // Same category
        if ($original->product_category_id !== $substitute->product_category_id) {
            $score -= 40;
            $notes[] = 'Different equipment category — substitution crosses category boundary.';
        }

        // Same make (softer signal)
        if ($origProfile && $subProfile && $origProfile->make === $subProfile->make) {
            $notes[] = "Same manufacturer ({$origProfile->make}) — operational similarity expected.";
        }

        // Status penalties
        if ($substitute->current_status === 'damaged') {
            $score -= 30;
            $notes[] = 'Substitute is currently flagged as Damaged — requires manager sign-off.';
        } elseif ($substitute->current_status === 'rented') {
            $score -= 15;
            $notes[] = 'Substitute is currently Rented — date conflict risk must be verified.';
        } elseif ($substitute->current_status === 'maintenance') {
            $score -= 5;
            $notes[] = 'Substitute is on Maintenance Hold — may be available by rental date.';
        }

        if ($substitute->not_for_rent) {
            $score = 0;
            $notes[] = 'Substitute is flagged as Not for Rent — cannot be assigned.';
        }

        return ['score' => max(0, $score), 'notes' => $notes];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function approvalLevel(float $overall, bool $hasCriticalViolation): string
    {
        if ($hasCriticalViolation || $overall < self::APPROVAL_RECOMMENDED) return 'required';
        if ($overall < self::APPROVAL_NOT_REQUIRED) return 'recommended';
        return 'none';
    }

    private function approvalReason(string $level, array $violations, array $specMisses): ?string
    {
        if ($level === 'none') return null;

        $reasons = [];
        foreach (array_slice($violations, 0, 3) as $v) {
            $reasons[] = "[{$v['severity']}] {$v['rule_name']}";
        }
        foreach (array_slice($specMisses, 0, 2) as $m) {
            $reasons[] = "Spec miss: {$m}";
        }

        return implode(' | ', $reasons);
    }

    private function buildExplanation(
        Equipment $original,
        Equipment $substitute,
        float $overall,
        array $spec,
        array $rule,
        array $compat,
        string $approvalLevel
    ): string {
        $header = $approvalLevel === 'none'
            ? "✓ Recommended Substitute: {$substitute->equipment_name}"
            : ($approvalLevel === 'recommended'
                ? "⚠ Possible Substitute (Manager Approval Recommended): {$substitute->equipment_name}"
                : "⛔ Requires Manager Approval: {$substitute->equipment_name}");

        $lines = [$header, '', "Overall Score: {$overall}/100", ''];

        if (!empty($spec['comparison'])) {
            $lines[] = 'Specification Comparison:';
            foreach ($spec['comparison'] as $c) {
                $icon = $c['meets'] ? '✓' : '✗';
                $lines[] = "  {$icon} {$c['spec_label']}: {$c['original_val']} → {$c['sub_val']}";
            }
            $lines[] = '';
        }

        if (!empty($rule['supports'])) {
            $lines[] = 'Supporting Rules:';
            foreach ($rule['supports'] as $s) {
                $lines[] = "  + {$s['rule_name']}";
            }
            $lines[] = '';
        }

        if (!empty($rule['violations'])) {
            $lines[] = 'Warnings:';
            foreach ($rule['violations'] as $v) {
                $lines[] = "  ! [{$v['severity']}] {$v['rule_name']}: {$v['detail']}";
            }
            $lines[] = '';
        }

        if (!empty($compat['notes'])) {
            $lines[] = 'Compatibility Notes:';
            foreach ($compat['notes'] as $n) {
                $lines[] = "  · {$n}";
            }
        }

        return implode("\n", $lines);
    }

    private function findProfile(Equipment $equipment): ?EquipmentAiProfile
    {
        if (!$equipment->product_category_id) return null;

        return EquipmentAiProfile::where('category_id', $equipment->product_category_id)
            ->where(fn ($q) => $q
                ->where('make', $equipment->make ?? '')
                ->where('model', $equipment->model ?? ''))
            ->first();
    }
}

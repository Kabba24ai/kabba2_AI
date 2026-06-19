<?php

namespace App\Services\EquipmentAi;

use App\Enums\EquipmentAi\IntelligenceRuleType;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use App\Models\ProductManagement\ProductCategory;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class IntelligenceRuleGeneratorService
{
    // Assignment status tiers mirrored from AutoAssignDirectService —
    // the AI context needs to understand these priorities.
    private const ASSIGNMENT_STATUS_CONTEXT = <<<TEXT
Equipment Assignment Priority Rules (Kabba Direct Assign System):
- Tier 1 (Preferred): Available or Maintenance Hold equipment. Maintenance Hold equipment CAN be assigned — it is held for scheduled service, not broken.
- Tier 2 (Acceptable): Rented equipment. Can be assigned to a future order if the rental dates do not overlap. A conflict will be flagged in Schedule Conflicts.
- Tier 3 (Last Resort): Damaged equipment. Only assigned when no other unit exists. The damage conflict surfaces in Schedule Conflicts for manager review.
- Overdue: Equipment past its return date that has not been returned. Treated as a conflict — upcoming orders on this equipment are flagged immediately.
- Not for Rent: Equipment flagged as not_for_rent=true is NEVER assigned by the system.
TEXT;

    public function __construct(private OpenAIService $ai) {}

    /**
     * Generate intelligence rules for an entire category.
     * Returns the number of rules created.
     */
    public function generateForCategory(ProductCategory $category, int $createdBy): int
    {
        $profiles      = $this->getProfileSummaries($category->id);
        $compKeys      = $this->getComparisonKeys($category->id);
        $existingRules = $this->getExistingRuleSummaries($category->id);

        $prompt = $this->buildCategoryPrompt($category, $profiles, $compKeys, $existingRules);
        $raw    = $this->callAi($prompt);

        return $this->persistRules($raw, $category->id, null, $createdBy);
    }

    /**
     * Generate intelligence rules for a specific make/model profile.
     */
    public function generateForProfile(EquipmentAiProfile $profile, int $createdBy): int
    {
        $category      = $profile->category;
        $compKeys      = $this->getComparisonKeys($category->id);
        $specs         = $this->getProfileSpecs($profile);
        $existingRules = $this->getExistingRuleSummaries($category->id, $profile->id);

        $prompt = $this->buildProfilePrompt($profile, $category, $compKeys, $specs, $existingRules);
        $raw    = $this->callAi($prompt);

        return $this->persistRules($raw, $category->id, $profile->id, $createdBy);
    }

    // ── Prompt Builders ───────────────────────────────────────────────

    private function buildCategoryPrompt(
        ProductCategory $category,
        array $profiles,
        array $compKeys,
        array $existingRules
    ): string {
        $profileList = collect($profiles)->map(fn ($p) =>
            "  - {$p['make']} {$p['model']}" . ($p['key_specs'] ? " | Key specs: {$p['key_specs']}" : '')
        )->implode("\n");

        $keyList = collect($compKeys)->map(fn ($k) =>
            "  - {$k['spec_label']}" . ($k['spec_unit'] ? " ({$k['spec_unit']})" : '')
        )->implode("\n");

        $existingList = $existingRules
            ? "Existing rules (do NOT duplicate these):\n" . collect($existingRules)->map(fn ($r) => "  - [{$r['type']}] {$r['name']}")->implode("\n")
            : 'No existing rules yet.';

        return <<<PROMPT
You are a senior equipment rental industry consultant with 20+ years of experience.

Your task: Generate practical Equipment Intelligence Rules for a rental company's "{$category->title}" category.

These rules represent BUSINESS JUDGMENT and RENTAL EXPERIENCE — NOT raw specifications.
Hard specs come from a separate database. Do not invent or repeat specifications.

EQUIPMENT IN THIS CATEGORY:
{$profileList}

KEY COMPARISON CRITERIA (specs customers care most about):
{$keyList}

{self::ASSIGNMENT_STATUS_CONTEXT}

{$existingList}

WHAT TO GENERATE:
Generate 8–14 practical intelligence rules covering:
1. When each type of machine is most suitable (and when it is NOT)
2. Substitution guidance — when two machines are truly interchangeable vs. when they are not
3. Delivery/terrain considerations (soft ground, tight access, steep grades, residential vs. commercial)
4. Scheduling intelligence (which machines stay rented longer, which return sooner, seasonal patterns)
5. Customer expectation management (when to warn customer before substituting)
6. Safety considerations specific to this category
7. Productivity rules (which machine type is faster to set up, which needs more space, etc.)

TAGS to use where relevant: residential, commercial, tree_service, concrete, landscaping, soft_ground,
tight_access, long_distance_delivery, customer_pickup, substitute_allowed, substitute_not_allowed,
quick_setup, slow_setup, heavyweight, lightweight, frequent_repositioning, stationary_use,
manager_approval_required, no_manager_approval

OUTPUT FORMAT — Return ONLY a valid JSON array, no markdown, no extra text:
[
  {
    "rule_type": "suitability",
    "rule_name": "Short descriptive name (max 80 chars)",
    "condition": "Describe when/where this rule applies.",
    "recommendation": "What the system or staff should do or recommend.",
    "reason": "Why this recommendation makes sense for rental operations.",
    "priority": 75,
    "confidence_score": 0.85,
    "source_type": "ai_generated",
    "tags": ["residential", "soft_ground"]
  }
]

Priority scale: 90–100 = critical safety/approval rules | 70–89 = strong operational guidance | 50–69 = helpful context | 1–49 = low-priority nuance
Confidence scale: 0.90+ = well-established industry knowledge | 0.70–0.89 = generally true, some exceptions | below 0.70 = judgment call
PROMPT;
    }

    private function buildProfilePrompt(
        EquipmentAiProfile $profile,
        ProductCategory $category,
        array $compKeys,
        array $specs,
        array $existingRules
    ): string {
        $specList = collect($specs)->map(fn ($s) =>
            "  - {$s['label']}: {$s['value']}" . ($s['unit'] ? " {$s['unit']}" : '')
        )->implode("\n");

        $keyList = collect($compKeys)->map(fn ($k) =>
            "  - {$k['spec_label']}" . ($k['spec_unit'] ? " ({$k['spec_unit']})" : '')
        )->implode("\n");

        $existingList = $existingRules
            ? "Existing rules for this model (do NOT duplicate):\n" . collect($existingRules)->map(fn ($r) => "  - [{$r['type']}] {$r['name']}")->implode("\n")
            : 'No existing model-specific rules.';

        return <<<PROMPT
You are a senior equipment rental industry consultant.

Generate Equipment Intelligence Rules SPECIFIC to the {$profile->make} {$profile->model} ({$category->title}).

KNOWN SPECIFICATIONS FOR THIS MODEL:
{$specList}

KEY COMPARISON CRITERIA FOR THIS CATEGORY:
{$keyList}

{self::ASSIGNMENT_STATUS_CONTEXT}

{$existingList}

FOCUS AREAS for model-specific rules:
1. What specific job types is this exact model best suited for?
2. What are its practical delivery limitations (weight, trailer requirements, road laws)?
3. What substitute models are acceptable vs. problematic for THIS model's customers?
4. Are there known quirks, setup requirements, or operator skill considerations?
5. When should customers be warned before receiving this model as a substitute?

Generate 4–8 model-specific rules.
OUTPUT FORMAT — Return ONLY a valid JSON array:
[
  {
    "rule_type": "suitability|limitation|substitution|scheduling|safety|delivery|productivity|terrain|customer_preference|application_use_case",
    "rule_name": "Short name max 80 chars",
    "condition": "When/where this applies.",
    "recommendation": "What to do.",
    "reason": "Why.",
    "priority": 70,
    "confidence_score": 0.80,
    "source_type": "ai_generated",
    "tags": ["tag1", "tag2"]
  }
]
PROMPT;
    }

    // ── AI Call ───────────────────────────────────────────────────────

    private function callAi(string $prompt): array
    {
        try {
            $response = $this->ai->chatCompletion([
                ['role' => 'system', 'content' => 'You are an expert equipment rental industry consultant. You return only valid JSON arrays. Never include markdown, code blocks, or explanatory text outside the JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ], [
                'temperature' => 0.4,
                'max_tokens'  => 4000,
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            $content = trim($content);

            // Strip any accidental markdown fences
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);
            if (!is_array($parsed)) {
                Log::warning('IntelligenceRuleGenerator: AI returned non-array JSON', ['raw' => $content]);
                return [];
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('IntelligenceRuleGenerator: AI call failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ── Persistence ───────────────────────────────────────────────────

    private function persistRules(array $rules, int $categoryId, ?int $profileId, int $createdBy): int
    {
        $validTypes = collect(IntelligenceRuleType::cases())->pluck('value')->toArray();
        $count = 0;

        foreach ($rules as $rule) {
            if (!isset($rule['rule_type'], $rule['rule_name'], $rule['condition'], $rule['recommendation'], $rule['reason'])) {
                continue;
            }

            if (!in_array($rule['rule_type'], $validTypes)) {
                continue;
            }

            EquipmentIntelligenceRule::create([
                'equipment_category_id' => $categoryId,
                'equipment_profile_id'  => $profileId,
                'rule_type'             => $rule['rule_type'],
                'rule_name'             => substr($rule['rule_name'], 0, 255),
                'condition'             => $rule['condition'],
                'recommendation'        => $rule['recommendation'],
                'reason'                => $rule['reason'],
                'priority'              => max(1, min(100, (int) ($rule['priority'] ?? 50))),
                'confidence_score'      => max(0, min(1, (float) ($rule['confidence_score'] ?? 0.70))),
                'tags'                  => is_array($rule['tags'] ?? null) ? $rule['tags'] : [],
                'source_type'           => 'ai_generated',
                'approved_by_admin'     => false, // always starts pending
                'is_active'             => true,
                'created_by'            => $createdBy,
            ]);

            $count++;
        }

        return $count;
    }

    // ── Data Helpers ─────────────────────────────────────────────────

    private function getProfileSummaries(int $categoryId): array
    {
        return EquipmentAiProfile::where('category_id', $categoryId)
            ->whereHas('specifications')
            ->get(['id', 'make', 'model'])
            ->map(function ($p) {
                $keySpecs = $p->specifications()
                    ->where('is_key_comparison', true)
                    ->whereNotNull('spec_value')
                    ->get(['spec_label', 'spec_value', 'spec_unit'])
                    ->map(fn ($s) => "{$s->spec_label}: {$s->spec_value}" . ($s->spec_unit ? " {$s->spec_unit}" : ''))
                    ->implode(', ');

                return ['make' => $p->make, 'model' => $p->model, 'key_specs' => $keySpecs];
            })
            ->toArray();
    }

    private function getComparisonKeys(int $categoryId): array
    {
        return EquipmentCategoryComparisonKey::where('category_id', $categoryId)
            ->get(['spec_label', 'spec_unit'])
            ->toArray();
    }

    private function getProfileSpecs(EquipmentAiProfile $profile): array
    {
        return $profile->specifications()
            ->whereNotNull('spec_value')
            ->get(['spec_label', 'spec_value', 'spec_unit'])
            ->map(fn ($s) => ['label' => $s->spec_label, 'value' => $s->spec_value, 'unit' => $s->spec_unit])
            ->toArray();
    }

    private function getExistingRuleSummaries(int $categoryId, ?int $profileId = null): array
    {
        return EquipmentIntelligenceRule::where('equipment_category_id', $categoryId)
            ->when($profileId, fn ($q) => $q->where('equipment_profile_id', $profileId))
            ->withTrashed()
            ->get(['rule_type', 'rule_name'])
            ->map(fn ($r) => ['type' => $r->rule_type->value, 'name' => $r->rule_name])
            ->toArray();
    }
}

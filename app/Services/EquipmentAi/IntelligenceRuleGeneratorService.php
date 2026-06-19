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
You are a senior equipment rental industry consultant with 25+ years of hands-on field experience managing
a full-service rental fleet across residential, commercial, industrial, tree service, landscaping, and
construction segments.

Your task: Generate a COMPREHENSIVE set of Equipment Intelligence Rules for a rental company's
"{$category->title}" category. Be exhaustive — cover every angle that a rental dispatch manager,
salesperson, or scheduling system would need to know to make smart decisions.

These rules represent BUSINESS JUDGMENT and RENTAL EXPERIENCE — NOT raw specifications.
Hard specs come from a separate verified database. Do not invent or contradict specifications.

══════════════════════════════════════════════════════
EQUIPMENT IN THIS CATEGORY (with key specs where available):
══════════════════════════════════════════════════════
{$profileList}

══════════════════════════════════════════════════════
KEY COMPARISON CRITERIA (what customers care most about):
══════════════════════════════════════════════════════
{$keyList}

══════════════════════════════════════════════════════
ASSIGNMENT STATUS RULES (the system's direct-assign logic):
══════════════════════════════════════════════════════
{self::ASSIGNMENT_STATUS_CONTEXT}

══════════════════════════════════════════════════════
EXISTING RULES (DO NOT DUPLICATE):
══════════════════════════════════════════════════════
{$existingList}

══════════════════════════════════════════════════════
GENERATE RULES COVERING ALL OF THESE DIMENSIONS:
══════════════════════════════════════════════════════

1. SUITABILITY — When is each machine type the RIGHT choice?
   - Job type fit (residential, commercial, industrial, tree service, concrete, landscaping)
   - Site condition fit (soft ground, slopes, tight spaces, overhead clearance, indoor use)
   - Task fit (frequent repositioning vs. stationary work, short duration vs. multi-day)
   - Operator experience (which machines require more skill, which are beginner-friendly)
   - Customer type (homeowner vs. contractor vs. professional crew)

2. LIMITATIONS — When should we NOT use a machine or warn the customer?
   - Physical site limitations (weight limits on surfaces, minimum clearances, road access)
   - Operator limitations (certification requirements, physical demands, complexity)
   - Environmental conditions (wind, rain, grade, surface stability)
   - Job scope mismatches (overkill for small jobs, undersized for large jobs)
   - Time/rental duration mismatches (not worth setup for <4 hour jobs, etc.)

3. SUBSTITUTION — When can machines be swapped, and when can they NOT?
   - Safe substitutions (same class, higher spec — always acceptable)
   - Acceptable upgrades (bigger machine, different type, customer must be informed)
   - Problematic swaps (changes machine type, different delivery requirements, different operator skill)
   - Unacceptable substitutions (never swap these without manager approval)
   - Cross-category substitutions (when a different category machine could serve the same purpose)

4. SCHEDULING — Operational patterns that affect assignment decisions
   - Average rental duration for this category (helps predict return date accuracy)
   - Which machines tend to run late / come back late (plan buffer time)
   - Seasonal demand patterns (which months are busiest, which are slow)
   - Same-day availability patterns (how often are units available for turnaround?)
   - Machines that need post-rental inspection/servicing time before next rental

5. DELIVERY — Logistics and transport considerations
   - Trailer requirements (does this require a specific trailer type or hitch class?)
   - Delivery distance considerations (is it worth delivering long distance? what's the threshold?)
   - Setup time on arrival (how long does customer need to set up vs. just drop and go?)
   - Pickup complexity (fuel checks, damage inspection, cleaning required before departure)
   - Weight and size implications for delivery routing (bridge weight limits, road width, etc.)

6. TERRAIN — Ground condition and site environment rules
   - Soft ground or wet conditions (which machines are acceptable, which will sink or damage lawn)
   - Hard surfaces (concrete, asphalt — any ground damage concerns?)
   - Slopes and grades (maximum grade per machine type, tipping risks)
   - Gravel, sand, or uneven terrain considerations
   - Indoor vs. outdoor use (fumes, clearance, flooring damage)

7. SAFETY — Rules that exist to protect the customer and the company
   - Fall arrest and tie-off requirements (if applicable)
   - Ground support requirements before setup
   - Overhead hazard awareness (power lines, tree branches)
   - Load limits and overloading risks
   - Unstable surfaces or setup conditions that require refusal

8. PRODUCTIVITY — Matching machine to job efficiency
   - Which machines get the job done faster for a given task
   - Which machines have significant setup overhead that reduces productivity on short jobs
   - Multi-task capability (can this machine do more than one job type?)
   - Fuel efficiency and runtime between refueling (relevant for full-day rentals)

9. CUSTOMER PREFERENCE — Managing expectations and satisfaction
   - When to proactively call the customer before making a substitution
   - Which substitutions typically cause complaints (machine is larger, heavier, different controls)
   - Which upgrades customers are typically happy to receive without a call
   - Price difference handling (when substitute is higher value — communicate the upgrade)
   - First-time renters vs. repeat customers for this category (adjust guidance accordingly)

10. APPLICATION USE CASE — Specific job scenarios and their ideal equipment match
    - List common job types customers call in for this category and which model/type is ideal
    - Flag job types where customers often pick the wrong machine
    - Cross-reference job type with site conditions for combined guidance

══════════════════════════════════════════════════════
TAGS — use ALL that apply per rule:
══════════════════════════════════════════════════════
Job types: residential, commercial, industrial, tree_service, concrete, landscaping, roofing,
           gutter_work, painting, hvac, electrical, framing, demolition, excavation

Site conditions: soft_ground, wet_ground, hard_surface, indoor, outdoor, tight_access, steep_grade,
                 flat_terrain, overhead_hazard, power_lines_present, sloped_surface

Delivery: long_distance_delivery, short_distance_delivery, customer_pickup, heavy_haul,
          trailer_required, specialized_trailer, easy_dropoff, complex_setup_on_arrival

Machine traits: heavyweight, lightweight, quick_setup, slow_setup, frequent_repositioning,
                stationary_use, operator_certification_required, beginner_friendly,
                multi_day_rental, short_term_rental, high_maintenance

Substitution: substitute_allowed, substitute_not_allowed, upgrade_acceptable,
              customer_call_required, manager_approval_required, no_manager_approval

Seasonality: spring_peak, summer_peak, fall_demand, winter_slow, year_round

══════════════════════════════════════════════════════
OUTPUT FORMAT — Return ONLY a valid JSON array, no markdown, no extra text, no explanation:
══════════════════════════════════════════════════════
[
  {
    "rule_type": "suitability|limitation|substitution|scheduling|safety|delivery|productivity|terrain|customer_preference|application_use_case",
    "rule_name": "Short, specific descriptive name (max 80 chars)",
    "condition": "Detailed description of exactly when and where this rule applies. Be specific enough that a dispatch system or new employee could apply it correctly without additional guidance.",
    "recommendation": "Clear, actionable guidance on what to do or recommend when this rule fires. Include any specific steps, calls to make, or approvals to seek.",
    "reason": "The rental industry rationale. Explain WHY this matters — what goes wrong if this rule is ignored, what the business impact is, what the customer experience impact is.",
    "priority": 75,
    "confidence_score": 0.85,
    "source_type": "ai_generated",
    "tags": ["tag1", "tag2", "tag3"]
  }
]

Priority scale:
  95–100 = Safety-critical or legal — must not be ignored
  85–94  = Strong operational rule — manager sign-off if violated
  70–84  = Important guidance — staff should follow without exception
  50–69  = Helpful context — use as a tiebreaker or advisory
  1–49   = Nuance or edge case — low frequency but worth knowing

Confidence scale:
  0.95+  = Universal industry standard, no meaningful exceptions
  0.85–0.94 = True for the vast majority of situations
  0.70–0.84 = Generally true, some job types or sites are exceptions
  0.50–0.69 = Judgment call — depends heavily on context
  below 0.50 = Speculative — flag for admin review before trusting

Generate as many rules as are genuinely useful and non-duplicative. Quality and coverage matter more than brevity.
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
You are a senior equipment rental industry consultant with 25+ years of hands-on fleet management experience.

Generate COMPREHENSIVE Equipment Intelligence Rules SPECIFIC to the {$profile->make} {$profile->model} ({$category->title}).
These rules represent model-specific knowledge that cannot be derived from specs alone.

══════════════════════════════════════════════════════
KNOWN SPECIFICATIONS FOR THIS MODEL:
══════════════════════════════════════════════════════
{$specList}

══════════════════════════════════════════════════════
KEY COMPARISON CRITERIA FOR THIS CATEGORY:
══════════════════════════════════════════════════════
{$keyList}

══════════════════════════════════════════════════════
ASSIGNMENT STATUS RULES (system direct-assign logic):
══════════════════════════════════════════════════════
{self::ASSIGNMENT_STATUS_CONTEXT}

══════════════════════════════════════════════════════
EXISTING RULES (DO NOT DUPLICATE):
══════════════════════════════════════════════════════
{$existingList}

══════════════════════════════════════════════════════
GENERATE MODEL-SPECIFIC RULES COVERING ALL OF THESE:
══════════════════════════════════════════════════════

1. SUITABILITY — What specific job types is the {$profile->make} {$profile->model} BEST suited for?
   - Optimal use cases where this model excels over competitors
   - Customer profiles that consistently get the best results with this model
   - Job sizes (acreage, volume, duration) where this model is the right choice
   - Site types where this model outperforms others in its class

2. LIMITATIONS — What should we warn customers about for this specific model?
   - Known physical limitations from its spec profile (weight, dimensions, reach, etc.)
   - Surface or terrain types where this model should NOT be used
   - Operator skill requirements unique to this model's controls or behavior
   - Situations where this model's specs make it wrong for an otherwise appropriate job
   - Rental duration constraints (e.g., minimum/maximum practical rental)

3. SUBSTITUTION — Which other models are acceptable substitutes, and under what conditions?
   - Models that can substitute without customer impact (close match in specs)
   - Models that substitute acceptably with a customer call (slight difference)
   - Models that should NEVER substitute without manager approval (significant mismatch)
   - When this model serves AS a substitute for a higher-demand model
   - Any cross-brand or cross-class substitutions that are commonly acceptable

4. DELIVERY — Logistics specifics for this model
   - Trailer class and hitch requirements for this specific weight/size
   - Maximum practical delivery distance given this model's rental value and setup time
   - Any transport permits or regulations triggered by this model's dimensions or weight
   - Setup/teardown time on-site that affects delivery crew scheduling
   - Fuel level requirements and cleaning expectations at return

5. SCHEDULING — How this model behaves in the rental cycle
   - Typical rental duration patterns (is it usually rented for 1 day, 1 week, 2+ weeks?)
   - Does this model tend to come back late? Does the customer often extend?
   - How long does this model need for inspection and servicing between rentals?
   - Seasonal demand patterns specific to this model's primary use cases
   - Any known high-demand periods where this model needs to be reserved earlier

6. SAFETY — Model-specific safety considerations
   - Any known operating hazards specific to this make/model's design
   - Slope or grade limits derived from its spec profile
   - Ground support or stabilizer requirements
   - Overhead or environmental clearances specific to its dimensions
   - Required personal protective equipment for operating this model

7. TERRAIN — Ground and surface considerations for this model
   - Ground pressure relative to its weight and footprint (lawn damage risk?)
   - Performance on slopes and grades relative to its specs
   - Wet vs. dry conditions — how does this model's spec profile affect rain-day rentals?
   - Indoor vs. outdoor restrictions (fumes, noise, clearance)

8. PRODUCTIVITY — How this model performs on real jobs
   - Where this model's specs translate to faster job completion vs. competitors
   - Where this model's specs make it SLOWER or require more passes/cycles
   - Any unique features or capabilities that affect productivity in this category
   - Fuel consumption and runtime estimates for full-day jobs

9. CUSTOMER PREFERENCE — Managing this model's customer experience
   - Are there customers who specifically request this make/model by name?
   - What are common complaints when customers receive this model as a substitute?
   - What are common positive surprises when customers receive this model?
   - How should staff describe this model to a customer who is unfamiliar?

10. APPLICATION USE CASE — Specific real-world job scenarios
    - 3–5 specific job scenarios where this exact model is the ideal choice
    - Job scenarios where this model might seem right but is actually the wrong choice
    - Combined site + task conditions that together make this model uniquely appropriate

══════════════════════════════════════════════════════
TAGS — use ALL that apply per rule:
══════════════════════════════════════════════════════
Job types: residential, commercial, industrial, tree_service, concrete, landscaping, roofing,
           gutter_work, painting, hvac, electrical, framing, demolition, excavation

Site conditions: soft_ground, wet_ground, hard_surface, indoor, outdoor, tight_access, steep_grade,
                 flat_terrain, overhead_hazard, power_lines_present, sloped_surface

Delivery: long_distance_delivery, short_distance_delivery, customer_pickup, heavy_haul,
          trailer_required, specialized_trailer, easy_dropoff, complex_setup_on_arrival

Machine traits: heavyweight, lightweight, quick_setup, slow_setup, frequent_repositioning,
                stationary_use, operator_certification_required, beginner_friendly,
                multi_day_rental, short_term_rental, high_maintenance

Substitution: substitute_allowed, substitute_not_allowed, upgrade_acceptable,
              customer_call_required, manager_approval_required, no_manager_approval

Seasonality: spring_peak, summer_peak, fall_demand, winter_slow, year_round

══════════════════════════════════════════════════════
OUTPUT FORMAT — Return ONLY a valid JSON array, no markdown, no extra text:
══════════════════════════════════════════════════════
[
  {
    "rule_type": "suitability|limitation|substitution|scheduling|safety|delivery|productivity|terrain|customer_preference|application_use_case",
    "rule_name": "Short, specific descriptive name (max 80 chars)",
    "condition": "Detailed description of exactly when and where this rule applies. Be specific enough that a dispatch system or new employee could apply it correctly.",
    "recommendation": "Clear, actionable guidance on what to do or recommend when this rule fires.",
    "reason": "The rental industry rationale — what goes wrong if this is ignored, what the business impact is.",
    "priority": 70,
    "confidence_score": 0.80,
    "source_type": "ai_generated",
    "tags": ["tag1", "tag2", "tag3"]
  }
]

Priority scale:
  95–100 = Safety-critical or legal — must not be ignored
  85–94  = Strong operational rule — manager sign-off if violated
  70–84  = Important guidance — staff should follow without exception
  50–69  = Helpful context — use as a tiebreaker or advisory
  1–49   = Nuance or edge case — low frequency but worth knowing

Confidence scale:
  0.95+  = Universal industry standard, no meaningful exceptions
  0.85–0.94 = True for the vast majority of situations
  0.70–0.84 = Generally true, some job types or sites are exceptions
  0.50–0.69 = Judgment call — depends heavily on context
  below 0.50 = Speculative — flag for admin review before trusting

Generate as many model-specific rules as are genuinely useful and non-duplicative.
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
                'max_tokens'  => 16000,
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

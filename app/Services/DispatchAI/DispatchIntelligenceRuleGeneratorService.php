<?php

namespace App\Services\DispatchAI;

use App\Enums\Dispatch\DispatchIntelligenceRuleType;
use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Dispatch\DispatchAiEquipmentRule;
use App\Models\Dispatch\DispatchAiSettings;
use App\Models\Dispatch\DispatchAiTrailer;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Dispatch\DispatchIntelligenceRule;
use App\Models\Iam\Personnel\User;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class DispatchIntelligenceRuleGeneratorService
{
    public function __construct(private OpenAIService $ai) {}

    public function generate(int $createdBy): int
    {
        $context       = $this->buildContext();
        $existingRules = $this->getExistingRuleSummaries();
        $prompt        = $this->buildPrompt($context, $existingRules);
        $raw           = $this->callAi($prompt);

        return $this->persistRules($raw, $createdBy);
    }

    // ── Prompt ───────────────────────────────────────────────────────

    private function buildPrompt(array $ctx, array $existingRules): string
    {
        $driverList = collect($ctx['drivers'])->map(fn ($d) =>
            "  - {$d['name']}" .
            ($d['cdl_a'] ? ' [CDL-A]' : ($d['cdl_b'] ? ' [CDL-B]' : '')) .
            ($d['skill_rating'] ? " | Skill: {$d['skill_rating']}/5" : '') .
            ($d['home_store_id'] ? " | Home Store: {$d['home_store_id']}" : '')
        )->implode("\n");

        $truckList = collect($ctx['trucks'])->map(fn ($t) =>
            "  - {$t['name']} | GVWR: {$t['gvwr']} | Tow: {$t['tow_rating']} | CDL: " . ($t['cdl_required'] ? 'Yes' : 'No')
        )->implode("\n");

        $trailerList = collect($ctx['trailers'])->map(fn ($t) =>
            "  - {$t['name']} | Payload: {$t['payload_capacity']}lbs | Deck: {$t['deck_length']}ft | Hitch: {$t['hitch_type']}"
        )->implode("\n");

        $equipRules = collect($ctx['equipment_rules'])->map(fn ($r) =>
            "  - {$r['equipment_name']} [{$r['category']}]" .
            " | Min trailer: {$r['min_trailer_capacity']}lbs" .
            ($r['must_haul_alone'] ? ' | MUST HAUL ALONE' : '') .
            ($r['cdl_required'] ? ' | CDL Required' : '') .
            ($r['special_notes'] ? " | Notes: {$r['special_notes']}" : '')
        )->implode("\n");

        $existingList = $existingRules
            ? "Existing rules (DO NOT duplicate):\n" . collect($existingRules)->map(fn ($r) => "  - [{$r['type']}] {$r['name']}")->implode("\n")
            : 'No existing rules yet.';

        $settings = $ctx['settings'];

        return <<<PROMPT
You are a senior dispatch operations consultant with 20+ years of experience managing heavy equipment rental fleets.

Your task: Generate a COMPREHENSIVE set of Dispatch Intelligence Rules for a heavy equipment rental company.
These rules represent OPERATIONAL EXPERIENCE and BUSINESS JUDGMENT that goes beyond raw dispatch algorithms.

══════════════════════════════════════════════════════
CURRENT FLEET CONFIGURATION:
══════════════════════════════════════════════════════

DRIVERS:
{$driverList}

TRUCKS:
{$truckList}

TRAILERS:
{$trailerList}

EQUIPMENT LOADING RULES (configured):
{$equipRules}

CURRENT AI POLICY SETTINGS:
  - Prefer same driver for returns: {$settings['prefer_same_driver_for_returns']}
  - Allow early delivery: {$settings['allow_early_delivery']}
  - Route: minimize miles={$settings['route_minimize_miles']}, batch deliveries={$settings['route_batch_nearby_deliveries']}, batch pickups={$settings['route_batch_nearby_pickups']}

══════════════════════════════════════════════════════
EXISTING RULES (DO NOT DUPLICATE):
══════════════════════════════════════════════════════
{$existingList}

══════════════════════════════════════════════════════
GENERATE RULES COVERING ALL OF THESE DIMENSIONS:
══════════════════════════════════════════════════════

1. DRIVER ASSIGNMENT — Who should drive what and when?
   - CDL requirement matching (which deliveries need CDL-A vs CDL-B vs no CDL)
   - Skill-based matching (heavy/specialized equipment → higher skill rating drivers)
   - Driver-customer relationship preferences (regular customers who prefer certain drivers)
   - New driver restrictions (what types of deliveries should new/low-skill drivers avoid)
   - Driver fatigue and back-to-back scheduling considerations
   - Distance limits per driver per day based on fleet capacity
   - Which drivers handle long-haul deliveries vs local city routes

2. ROUTING — How to sequence and cluster jobs for efficiency
   - Geographic batching rules (what city/area radius qualifies as "same cluster")
   - When NOT to cluster (conflicting delivery windows, incompatible equipment loads)
   - Return trip logic (when does it make sense to do a pickup on the return trip)
   - Urban vs rural routing differences (travel time assumptions, access restrictions)
   - Morning vs afternoon delivery windows based on customer type (residential vs contractor)
   - Rush hour avoidance rules for specific metro areas or routes

3. SCHEDULING — Timing intelligence the algorithm can't derive from dates alone
   - Minimum lead time between booking and delivery for large equipment
   - How far in advance to schedule pickups after delivery completion
   - Same-day delivery rules (when can same-day be committed, when to refuse)
   - Buffer time between deliveries on the same day for the same driver
   - Multi-day rental return pattern (typical equipment kept longer than booked — flag early)
   - Friday afternoon vs Monday morning preference patterns for certain customer types

4. LOADING — Physical and logistical loading intelligence
   - Which equipment combinations CAN share a trailer
   - Which combinations CANNOT be loaded together (weight, dimension, clearance)
   - Loading order (heavier equipment loaded first, lighter on top)
   - When to use a second truck instead of overloading
   - Attachment/accessory co-loading rules (auger with mini skid, etc.)
   - Trailer type preferences per equipment class (gooseneck vs bumper pull situations)

5. CUSTOMER PREFERENCE — Customer-specific delivery intelligence
   - Residential customers: time window restrictions (no early morning, no after 5pm)
   - Commercial/contractor sites: early start preferred, weekend access restrictions
   - First-time customers: extra driver prep time, slower delivery pace
   - Long-term customers: may accept flexible windows, driver knows site
   - Customers with multiple active orders: coordinate delivery sequencing
   - Call-ahead requirements (which customer types always need a call 30min before)

6. PRIORITY OVERRIDE — When to override the AI's default priority logic
   - Customer has a hard job start date (crew on site) — flag as critical
   - Equipment is going directly from one customer to another (mission critical pickup)
   - Weather forecasts impacting delivery windows (move up priority if rain expected)
   - Permit-required deliveries that need to hit specific time windows
   - VIP or high-revenue customers who should be prioritized
   - Equipment overdue for pickup (liability risk — elevate priority)

7. SEASONAL — Demand and staffing patterns by time of year
   - Spring peak: landscaping and construction season start — fleet at capacity, buffer scheduling
   - Summer: tree service and commercial construction peak — CDL drivers in high demand
   - Fall: harvest season, end-of-project pickups — plan for higher return volume
   - Winter slow: longer delivery windows acceptable, reduce driver overtime
   - Holiday restrictions: no deliveries Christmas Eve, Christmas, New Year's Day
   - Weekend patterns: Saturday deliveries typical for residential, Sunday rare

8. GEOGRAPHIC — Area-specific delivery rules
   - Narrow road or low bridge clearance zones (no large trailers)
   - Gated communities: call-ahead required, no early delivery
   - Urban high-rise construction: coordinate with site super, no self-setup
   - Farm/rural addresses: verify road access before sending heavy trailer
   - State line crossings: check if CDL or permit requirements change
   - Long-distance threshold (beyond X miles = overnight planning required)

9. SAFETY — Safety-critical dispatch rules
   - Weather conditions that should trigger delivery hold (ice, high winds for lifts)
   - Never schedule boom lift or aerial work platform delivery during predicted high winds
   - Equipment requiring two people to unload safely
   - Overweight load permit requirements based on trailer payload thresholds
   - Driver pre-trip inspection requirements for heavy hauls
   - Maximum hours of driving per dispatch day (DOT compliance reminder)

10. OPERATIONAL — General best practices that improve dispatch quality
    - Confirm delivery window with customer the day before for all orders
    - Photo documentation requirements at delivery and pickup
    - Damage report pre-dispatch check (equipment marked damaged should not be dispatched)
    - Fuel level requirement before long-haul deliveries
    - Equipment cleanliness standard before customer delivery
    - Post-pickup inspection protocol before equipment goes back to yard

══════════════════════════════════════════════════════
TAGS — use ALL that apply per rule:
══════════════════════════════════════════════════════
Driver: cdl_required, cdl_a_only, high_skill_required, low_skill_ok, new_driver_restriction,
        driver_fatigue, driver_continuity, local_only, long_haul_capable

Customer: residential, commercial, contractor, first_time_customer, vip_customer, repeat_customer,
          call_ahead_required, time_window_strict, flexible_window

Equipment: heavy_equipment, aerial_work_platform, boom_lift, scissor_lift, mini_excavator,
           skid_steer, trailer_gooseneck, trailer_bumper_pull, shared_load_ok, solo_load_required

Timing: same_day_ok, same_day_prohibited, morning_preferred, afternoon_preferred,
        weekend_ok, weekend_restricted, holiday_restricted, buffer_time_required

Geography: urban, rural, tight_access, bridge_restriction, gated_community, long_distance,
           short_distance, state_line, permit_required

Safety: two_person_required, weather_hold, wind_restriction, dot_compliance, permit_load,
        pre_trip_inspection, overweight_load

Seasonal: spring_peak, summer_peak, fall_harvest, winter_slow, year_round

Priority: mission_critical, high_priority, standard, low_priority, deferrable

══════════════════════════════════════════════════════
OUTPUT FORMAT — Return ONLY a valid JSON array, no markdown, no extra text:
══════════════════════════════════════════════════════
[
  {
    "rule_type": "driver_assignment|routing|scheduling|loading|customer_preference|priority_override|seasonal|geographic|safety|operational",
    "rule_name": "Short specific name (max 80 chars)",
    "condition": "Detailed description of exactly when this rule applies — specific enough that a dispatcher or AI could trigger it without additional guidance.",
    "recommendation": "Clear, actionable instruction. Include specific steps, thresholds, or escalation paths.",
    "reason": "The operational rationale — what goes wrong if this is ignored, what the business or safety impact is.",
    "priority": 75,
    "confidence_score": 0.85,
    "source_type": "ai_generated",
    "tags": ["cdl_required", "heavy_equipment", "long_haul_capable"]
  }
]

Priority scale:
  95–100 = Safety-critical or DOT compliance — must not be ignored
  85–94  = Strong operational rule — manager should review if violated
  70–84  = Important guidance — dispatcher should follow without exception
  50–69  = Helpful context — use as a tiebreaker or advisory input
  1–49   = Edge case or nuance — low frequency but worth knowing

Confidence scale:
  0.95+  = Industry standard, universal applicability
  0.85–0.94 = True for the vast majority of situations
  0.70–0.84 = Generally true with some exceptions
  0.50–0.69 = Judgment call — context-dependent
  below 0.50 = Speculative — needs admin validation before trusting

Generate as many rules as are genuinely useful and non-duplicative. Cover every dimension above.
PROMPT;
    }

    // ── AI Call ───────────────────────────────────────────────────────

    private function callAi(string $prompt): array
    {
        try {
            $response = $this->ai->chatCompletion([
                ['role' => 'system', 'content' => 'You are an expert heavy equipment rental dispatch operations consultant. Return only valid JSON arrays. Never include markdown, code blocks, or explanatory text outside the JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ], [
                'temperature' => 0.4,
                'max_tokens'  => 16000,
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            $content = trim($content);
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);
            if (!is_array($parsed)) {
                Log::warning('DispatchIntelligenceRuleGenerator: AI returned non-array JSON', ['raw' => $content]);
                return [];
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('DispatchIntelligenceRuleGenerator: AI call failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ── Persistence ───────────────────────────────────────────────────

    private function persistRules(array $rules, int $createdBy): int
    {
        $validTypes = collect(DispatchIntelligenceRuleType::cases())->pluck('value')->toArray();
        $count = 0;

        foreach ($rules as $rule) {
            if (!isset($rule['rule_type'], $rule['rule_name'], $rule['condition'], $rule['recommendation'], $rule['reason'])) {
                continue;
            }

            if (!in_array($rule['rule_type'], $validTypes)) {
                continue;
            }

            DispatchIntelligenceRule::create([
                'rule_type'         => $rule['rule_type'],
                'rule_name'         => substr($rule['rule_name'], 0, 255),
                'condition'         => $rule['condition'],
                'recommendation'    => $rule['recommendation'],
                'reason'            => $rule['reason'],
                'priority'          => max(1, min(100, (int) ($rule['priority'] ?? 50))),
                'confidence_score'  => max(0, min(1, (float) ($rule['confidence_score'] ?? 0.70))),
                'tags'              => is_array($rule['tags'] ?? null) ? $rule['tags'] : [],
                'source_type'       => 'ai_generated',
                'approved_by_admin' => false,
                'is_active'         => true,
                'created_by'        => $createdBy,
            ]);

            $count++;
        }

        return $count;
    }

    // ── Context Builder ───────────────────────────────────────────────

    private function buildContext(): array
    {
        $settings = DispatchAiSettings::instance();

        $drivers      = User::active()->where('is_driver', true)->orderBy('first_name')->get();
        $capabilities = DispatchAiDriverCapability::whereIn('user_id', $drivers->pluck('id'))->get()->keyBy('user_id');

        $driverData = $drivers->map(function ($d) use ($capabilities) {
            $cap = $capabilities->get($d->id);
            return [
                'name'             => $d->full_name,
                'cdl_a'            => (bool) $d->cdl_a,
                'cdl_b'            => (bool) $d->cdl_b,
                'skill_rating'     => $cap?->skill_rating ?? 3,
                'home_store_id'    => $cap?->home_store_id,
                'can_tow_gooseneck'=> $cap?->can_tow_gooseneck ?? false,
            ];
        })->values()->toArray();

        $trucks = DispatchAiTruck::where('is_active', true)->get()->map(fn ($t) => [
            'name'         => $t->truck_name,
            'gvwr'         => $t->gvwr,
            'tow_rating'   => $t->tow_rating,
            'cdl_required' => $t->cdl_required,
        ])->values()->toArray();

        $trailers = DispatchAiTrailer::where('is_active', true)->get()->map(fn ($t) => [
            'name'             => $t->trailer_name,
            'payload_capacity' => $t->payload_capacity,
            'deck_length'      => $t->deck_length,
            'hitch_type'       => $t->hitch_type,
            'cdl_required'     => $t->cdl_required,
        ])->values()->toArray();

        $equipmentRules = DispatchAiEquipmentRule::with('equipment.productCategory')->get()->map(fn ($r) => [
            'equipment_name'       => $r->equipment?->equipment_name,
            'category'             => $r->equipment?->productCategory?->title,
            'min_trailer_capacity' => $r->min_trailer_capacity,
            'must_haul_alone'      => $r->must_haul_alone,
            'cdl_required'         => $r->cdl_required,
            'special_notes'        => $r->special_notes,
        ])->values()->toArray();

        return [
            'drivers'         => $driverData,
            'trucks'          => $trucks,
            'trailers'        => $trailers,
            'equipment_rules' => $equipmentRules,
            'settings'        => [
                'prefer_same_driver_for_returns'  => $settings->prefer_same_driver_for_returns ? 'true' : 'false',
                'allow_early_delivery'            => $settings->allow_early_delivery ? 'true' : 'false',
                'route_minimize_miles'            => $settings->route_minimize_miles ? 'true' : 'false',
                'route_batch_nearby_deliveries'   => $settings->route_batch_nearby_deliveries ? 'true' : 'false',
                'route_batch_nearby_pickups'      => $settings->route_batch_nearby_pickups ? 'true' : 'false',
            ],
        ];
    }

    private function getExistingRuleSummaries(): array
    {
        return DispatchIntelligenceRule::withTrashed()
            ->get(['rule_type', 'rule_name'])
            ->map(fn ($r) => ['type' => $r->rule_type->value, 'name' => $r->rule_name])
            ->toArray();
    }
}

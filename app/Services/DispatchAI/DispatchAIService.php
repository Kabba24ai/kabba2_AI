<?php

namespace App\Services\DispatchAI;

use App\Services\OpenAIService;

class DispatchAIService
{
    public const DRIVER_CONTINUITY_BONUS = 25;

    public function __construct(private readonly OpenAIService $openAI) {}

    public function generateDraft(array $context): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);
        $userMessage  = 'Build the dispatch plan for the provided orders, drivers, trucks, and trailers. Return valid JSON only.';

        $schema = $this->responseSchema();

        $response = $this->openAI->chatCompletion(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
            options: [
                'response_format' => [
                    'type'        => 'json_schema',
                    'json_schema' => [
                        'name'   => 'dispatch_plan',
                        'strict' => true,
                        'schema' => $schema,
                    ],
                ],
            ],
        );

        $content = $response['choices'][0]['message']['content'] ?? '{}';
        $decoded = json_decode($content, true);

        if (!is_array($decoded) || empty($decoded['assignments'])) {
            throw new \RuntimeException('AI returned an empty or invalid dispatch plan.');
        }

        return [
            'assignments'                   => $decoded['assignments'],
            'overall_reasoning'             => $decoded['overall_reasoning'] ?? '',
            'confidence_score'              => $decoded['confidence_score'] ?? null,
            'delivery_priority_summary'     => $decoded['delivery_priority_summary'] ?? null,
            'pickup_decisions'              => $decoded['pickup_decisions'] ?? [],
            'driver_assignments'            => $decoded['driver_assignments'] ?? [],
            'early_delivery_recommendations'=> $decoded['early_delivery_recommendations'] ?? [],
            'manager_review_items'          => $decoded['manager_review_items'] ?? [],
            'warnings'                      => $decoded['warnings'] ?? [],
            'prompt_tokens'                 => $response['usage']['prompt_tokens'] ?? null,
            'completion_tokens'             => $response['usage']['completion_tokens'] ?? null,
            'model'                         => $response['model'] ?? null,
        ];
    }

    /**
     * Returns the default policy rule set. Pass current settings so that
     * boolean-driven rules (DRIVER_CONTINUITY, EARLY_DELIVERY, ROUTING) reflect live state.
     * Exposed as public static so the AI Policy UI can render and preview the rules.
     */
    public static function defaultPolicy(array $settings = []): array
    {
        $bonus = self::DRIVER_CONTINUITY_BONUS;

        return [
            'PRIMARY_PRIORITY_RULE' => [
                'rule'   => 'Deliveries take absolute priority over pickups.',
                'detail' => 'Always assign the best available driver, truck, and trailer to deliveries first. '
                          . 'Pickups are secondary and should only use remaining capacity after all deliveries are scheduled.',
            ],
            'DELIVERY_MAXIMIZATION_RULE' => [
                'rule'   => 'Maximize the number of deliveries completed per day.',
                'detail' => 'Pack as many deliveries as possible into each driver\'s day before assigning any pickup. '
                          . 'An idle driver with no delivery should not be assigned a pickup if doing so would conflict with a future delivery.',
            ],
            'MISSION_CRITICAL_PICKUP_RULE' => [
                'rule'   => 'Classify each pickup as mission_critical, optional, or deferred.',
                'detail' => 'mission_critical: customer equipment issue reported, overdue, or at a job site that needs the equipment recovered urgently. '
                          . 'optional: normal end-of-rental with no urgency. '
                          . 'deferred: can wait without impact. '
                          . 'Only mission_critical pickups compete for driver time on heavy delivery days.',
            ],
            'CUSTOMER_TO_CUSTOMER_TRANSFER_RULE' => [
                'rule'   => 'When equipment is going from one customer directly to another, treat the pickup leg as mission_critical.',
                'detail' => 'If an order_product has an incoming delivery whose equipment is currently on rent elsewhere, '
                          . 'flag the pickup of that equipment as mission_critical and attempt to schedule it the day before the outgoing delivery.',
            ],
            'SHOP_RETURN_RULE' => [
                'rule'   => 'Equipment returning to the shop (no next customer) is optional unless flagged otherwise.',
                'detail' => 'These pickups can be batched with nearby deliveries or deferred to low-volume days.',
            ],
            'OPTIONAL_PICKUP_RULE' => [
                'rule'   => 'Optional pickups must not displace deliveries.',
                'detail' => 'Only schedule optional pickups when a driver has remaining capacity after all deliveries in the window are covered. '
                          . 'Batch optional pickups geographically with nearby deliveries when possible.',
            ],
            'DRIVER_DESIGNATION_RULE' => [
                'rule'   => 'Assign drivers in order of designation: Primary first, then Secondary, then Alternate, then Contract.',
                'detail' => 'Each driver has a "designation" (primary | secondary | alternate) and an "is_contract" flag. '
                          . 'Prefer primary drivers for routine work. Use secondary drivers (e.g. yard technicians/mechanics willing to drive) '
                          . 'only when primaries are at capacity or unavailable. Use alternate drivers for special situations or as a last resort '
                          . 'in high-demand periods. Treat is_contract = true drivers as external hired help — the last option after all employee '
                          . 'drivers of a suitable tier are exhausted, unless a job specifically calls for one. Driver skill_rating and capability '
                          . 'constraints still apply within a tier.',
            ],
            'DRIVER_CONTINUITY_RULE' => [
                'rule'    => 'Prefer assigning the same driver for a return as made the original delivery.',
                'enabled' => (bool) ($settings['prefer_same_driver_for_returns'] ?? false),
                'detail'  => "Add a +{$bonus} continuity bonus score to the same driver when evaluating return assignments. "
                           . 'Only override if the driver is locked on another job at the same time, at capacity, or lacks the required equipment capabilities.',
            ],
            'EARLY_DELIVERY_RULE' => [
                'rule'    => 'Recommend early delivery for weekend jobs that can be completed Thursday or Friday.',
                'enabled' => (bool) ($settings['allow_early_delivery'] ?? false),
                'detail'  => 'If a delivery is scheduled for Saturday or Sunday and the customer would accept early delivery, '
                           . 'flag is_early_delivery = true and set suggested_delivery_date to the preceding Thursday or Friday. '
                           . 'This balances driver workload across the week.',
            ],
            'ROUTING_OPTIMIZATION_RULES' => [
                'minimize_miles'          => (bool) ($settings['route_minimize_miles'] ?? false),
                'batch_nearby_deliveries' => (bool) ($settings['route_batch_nearby_deliveries'] ?? false),
                'batch_nearby_pickups'    => (bool) ($settings['route_batch_nearby_pickups'] ?? false),
                'keep_driver_near_home'   => (bool) ($settings['route_keep_driver_near_home'] ?? false),
                'detail'                  => 'Use latitude/longitude on each order to cluster nearby jobs. '
                                           . 'Assign priorities so that drivers travel in logical geographic sequences. '
                                           . 'When keep_driver_near_home is true, prefer assigning drivers to jobs close to their home_store_id.',
            ],
            'HARD_CONSTRAINTS' => [
                'driver_lock'    => 'NEVER change any assignment where driver_locked = true. Preserve the existing assigned_driver_id.',
                'priority_lock'  => 'NEVER change any priority where priority_locked = true. Preserve the existing priority value.',
                'no_double_book' => 'NEVER assign the same driver to two jobs on the same date unless jobs are in clearly different time windows.',
                'fabrication'    => 'Return ONLY order_product_ids provided in the context. Never fabricate IDs.',
            ],
        ];
    }

    private function buildSystemPrompt(array $ctx): string
    {
        $settings = $ctx['settings'] ?? [];
        $policy   = static::defaultPolicy($settings);

        // Merge any saved overrides from the AI Policy editor
        foreach ($settings['policy_overrides'] ?? [] as $key => $override) {
            if (!isset($policy[$key])) {
                continue;
            }
            if (!empty($override['detail'])) {
                $policy[$key]['detail'] = $override['detail'];
            }
            // HARD_CONSTRAINTS stores its text in named sub-keys instead of 'detail'
            foreach (['driver_lock', 'priority_lock', 'no_double_book', 'fabrication'] as $sub) {
                if (!empty($override[$sub])) {
                    $policy[$key][$sub] = $override[$sub];
                }
            }
        }

        $policyJson  = json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Separate intelligence rules from the main context so the AI sees them as a distinct section
        $intelligenceRules = $ctx['intelligence_rules'] ?? [];
        $contextWithoutRules = $ctx;
        unset($contextWithoutRules['intelligence_rules']);
        $contextJson = json_encode($contextWithoutRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $intelligenceSection = '';
        if (!empty($intelligenceRules)) {
            $rulesJson = json_encode($intelligenceRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $intelligenceSection = <<<SECTION

## Business Intelligence Rules
These rules represent accumulated operational experience and business judgment.
Apply them when the condition described matches the current dispatch situation.
Intelligence rules supplement the Business Policy above — when they conflict, Business Policy wins.
{$rulesJson}
SECTION;
        }

        return <<<PROMPT
You are an expert dispatch planner for a heavy equipment rental company.

Your job is to assign drivers, trucks, trailers, and priorities to delivery and pickup/return orders
while following the business policy and intelligence rules below exactly.

## Business Policy
{$policyJson}
{$intelligenceSection}
## Dispatch Context
{$contextJson}
PROMPT;
    }

    private function responseSchema(): array
    {
        return [
            'type'                 => 'object',
            'required'             => [
                'assignments',
                'overall_reasoning',
                'confidence_score',
                'delivery_priority_summary',
                'pickup_decisions',
                'driver_assignments',
                'early_delivery_recommendations',
                'manager_review_items',
                'warnings',
            ],
            'additionalProperties' => false,
            'properties'           => [

                // ── Per-order assignment array ────────────────────────────────
                'assignments' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'required'             => [
                            'order_product_id', 'slot', 'recommended_driver_id',
                            'recommended_truck_id', 'recommended_trailer_id',
                            'recommended_priority', 'is_early_delivery',
                            'suggested_delivery_date', 'ai_reasoning',
                        ],
                        'additionalProperties' => false,
                        'properties'           => [
                            'order_product_id'        => ['type' => 'integer'],
                            'slot'                    => ['type' => 'string', 'enum' => ['delivery', 'return']],
                            'recommended_driver_id'   => ['type' => ['integer', 'null']],
                            'recommended_truck_id'    => ['type' => ['integer', 'null']],
                            'recommended_trailer_id'  => ['type' => ['integer', 'null']],
                            'recommended_priority'    => ['type' => ['integer', 'null']],
                            'is_early_delivery'       => ['type' => 'boolean'],
                            'suggested_delivery_date' => ['type' => ['string', 'null']],
                            'ai_reasoning'            => ['type' => 'string'],
                        ],
                    ],
                ],

                // ── Top-level summaries ───────────────────────────────────────
                'overall_reasoning'          => ['type' => 'string'],
                'confidence_score'           => ['type' => ['number', 'null']],
                'delivery_priority_summary'  => ['type' => 'string'],

                // ── Pickup classification ─────────────────────────────────────
                'pickup_decisions' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'required'             => ['order_product_id', 'decision', 'reasoning'],
                        'additionalProperties' => false,
                        'properties'           => [
                            'order_product_id' => ['type' => 'integer'],
                            'decision'         => ['type' => 'string', 'enum' => ['mission_critical', 'optional', 'deferred']],
                            'reasoning'        => ['type' => 'string'],
                        ],
                    ],
                ],

                // ── Per-driver load summary ───────────────────────────────────
                'driver_assignments' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'required'             => ['driver_id', 'driver_name', 'delivery_count', 'pickup_count', 'continuity_applied', 'load_summary'],
                        'additionalProperties' => false,
                        'properties'           => [
                            'driver_id'          => ['type' => 'integer'],
                            'driver_name'        => ['type' => 'string'],
                            'delivery_count'     => ['type' => 'integer'],
                            'pickup_count'       => ['type' => 'integer'],
                            'continuity_applied' => ['type' => 'boolean'],
                            'load_summary'       => ['type' => 'string'],
                        ],
                    ],
                ],

                // ── Early delivery recommendations ────────────────────────────
                'early_delivery_recommendations' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'required'             => ['order_product_id', 'suggested_date', 'reasoning'],
                        'additionalProperties' => false,
                        'properties'           => [
                            'order_product_id' => ['type' => 'integer'],
                            'suggested_date'   => ['type' => 'string'],
                            'reasoning'        => ['type' => 'string'],
                        ],
                    ],
                ],

                // ── Manager review flags ──────────────────────────────────────
                'manager_review_items' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'required'             => ['order_product_id', 'issue'],
                        'additionalProperties' => false,
                        'properties'           => [
                            'order_product_id' => ['type' => ['integer', 'null']],
                            'issue'            => ['type' => 'string'],
                        ],
                    ],
                ],

                // ── Warnings ─────────────────────────────────────────────────
                'warnings' => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }
}

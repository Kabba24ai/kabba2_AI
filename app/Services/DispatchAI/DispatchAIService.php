<?php

namespace App\Services\DispatchAI;

use App\Services\OpenAIService;

class DispatchAIService
{
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
            'assignments'      => $decoded['assignments'],
            'overall_reasoning'=> $decoded['overall_reasoning'] ?? '',
            'confidence_score' => $decoded['confidence_score'] ?? null,
            'prompt_tokens'    => $response['usage']['prompt_tokens'] ?? null,
            'completion_tokens'=> $response['usage']['completion_tokens'] ?? null,
            'model'            => $response['model'] ?? null,
        ];
    }

    private function buildSystemPrompt(array $ctx): string
    {
        $contextJson = json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an expert dispatch planner for a heavy equipment rental company.

Your job is to assign drivers, trucks, trailers, and priorities to delivery and return orders.

## Rules
1. NEVER change any assignment where driver_locked = true. Keep the existing assigned_driver_id for that slot.
2. NEVER change the priority where priority_locked = true. Keep the existing priority value.
3. If prefer_same_driver_for_returns = true, try to assign the same driver for a return as made the original delivery (delivery_driver_id field), unless that driver is unavailable or locked on another job at the same time.
4. Do not double-book a driver on the same date unless jobs are in different time windows.
5. Assign trucks and trailers based on equipment_rules (category requirements, payload capacity, hitch type, CDL requirements).
6. If allow_early_delivery = true and early_delivery_enabled = true, recommend early_delivery = true for weekend jobs that could be done on Thursday/Friday to balance driver workload.
7. Assign priorities to minimize total route miles where possible (route_minimize_miles setting).
8. Batch nearby deliveries for the same driver on the same day (route_batch_nearby_deliveries).
9. Prefer keeping drivers close to their home_store_id.
10. Return ONLY the orders provided — do not fabricate order_product_ids.

## Context
{$contextJson}
PROMPT;
    }

    private function responseSchema(): array
    {
        return [
            'type'                  => 'object',
            'required'              => ['assignments', 'overall_reasoning', 'confidence_score'],
            'additionalProperties'  => false,
            'properties'            => [
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
                            'order_product_id'       => ['type' => 'integer'],
                            'slot'                   => ['type' => 'string', 'enum' => ['delivery', 'return']],
                            'recommended_driver_id'  => ['type' => ['integer', 'null']],
                            'recommended_truck_id'   => ['type' => ['integer', 'null']],
                            'recommended_trailer_id' => ['type' => ['integer', 'null']],
                            'recommended_priority'   => ['type' => ['integer', 'null']],
                            'is_early_delivery'      => ['type' => 'boolean'],
                            'suggested_delivery_date'=> ['type' => ['string', 'null']],
                            'ai_reasoning'           => ['type' => 'string'],
                        ],
                    ],
                ],
                'overall_reasoning' => ['type' => 'string'],
                'confidence_score'  => ['type' => ['number', 'null']],
            ],
        ];
    }
}

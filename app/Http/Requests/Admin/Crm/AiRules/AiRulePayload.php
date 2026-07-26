<?php

namespace App\Http\Requests\Admin\Crm\AiRules;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared normalizer for the AI-rule create/update forms. Textarea list fields
 * (one item per line) are parsed into clean arrays for the JSON columns.
 */
class AiRulePayload
{
    private const LIST_FIELDS = [
        'evaluable_inputs',
        'ai_may_recommend',
        'ai_may_execute',
        'ai_must_not',
    ];

    public static function fromRequest(FormRequest $request): array
    {
        $data = $request->safe()->except(self::LIST_FIELDS);

        foreach (self::LIST_FIELDS as $field) {
            $data[$field] = self::lines($request->input($field));
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** @return array<int, string> */
    private static function lines(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}

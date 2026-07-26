<?php

namespace App\Http\Requests\Admin\Crm\AiRules;

use App\Enums\AiRules\AiRuleAuthority;
use App\Enums\AiRules\AiRuleImplementationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAiRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rule_key'   => ['required', 'string', 'max:191', 'regex:/^[a-z0-9_]+$/', Rule::unique('ai_rules', 'rule_key')],
            'rule_name'  => ['required', 'string', 'max:191'],
            'business_area' => ['nullable', 'string', 'max:191'],
            'purpose'    => ['nullable', 'string'],
            'trigger_description'       => ['nullable', 'string'],
            'evaluable_inputs'          => ['nullable', 'string'],
            'deterministic_action'      => ['nullable', 'string'],
            'ai_assessment_instruction' => ['nullable', 'string'],
            'ai_may_recommend'          => ['nullable', 'string'],
            'ai_may_execute'            => ['nullable', 'string'],
            'ai_must_not'               => ['nullable', 'string'],
            'required_human_reviewer'   => ['nullable', 'string', 'max:191'],
            'escalation_destination'    => ['nullable', 'string', 'max:191'],
            'authority'                 => ['required', new Enum(AiRuleAuthority::class)],
            'implementation_status'     => ['required', new Enum(AiRuleImplementationStatus::class)],
            'is_active'                 => ['nullable', 'boolean'],
            'effective_date'            => ['nullable', 'date'],
        ];
    }

    /** Fields that persist directly, with textarea lists parsed to arrays. */
    public function payload(): array
    {
        return AiRulePayload::fromRequest($this);
    }
}

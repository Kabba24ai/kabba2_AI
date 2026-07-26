<?php

namespace App\Enums\AiRules;

/**
 * The authority boundary of an AI rule. Execution authority is explicit, narrow,
 * and separately approved — a rule NEVER gains the right to act merely by
 * existing in the repository. The default for any new rule is RecommendOnly.
 */
enum AiRuleAuthority: string
{
    case RecommendOnly = 'recommend_only'; // AI may assess and recommend only
    case MayExecute = 'may_execute';       // AI may execute — must be deliberately granted

    public function label(): string
    {
        return match ($this) {
            self::RecommendOnly => 'AI may recommend',
            self::MayExecute    => 'AI may execute',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::RecommendOnly => 'bg-sky-100 text-sky-800',
            self::MayExecute    => 'bg-red-100 text-red-800',
        };
    }
}

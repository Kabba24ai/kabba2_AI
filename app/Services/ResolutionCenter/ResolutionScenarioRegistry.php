<?php

namespace App\Services\ResolutionCenter;

/**
 * Phase 3.4 — Operational Knowledge Framework.
 * Phase 3.5 — Manual Resolution Scenario Foundation: registered the
 * framework's second, real scenario here — exactly the one-line addition
 * Phase 3.4's design document said this would take.
 *
 * The single extensibility point this framework adds: a future scenario
 * (Equipment Exchange, Damage Settlement, etc.) is enabled by implementing
 * {@see ResolutionScenario} and adding one line here — no change to
 * ResolutionCenterService, the routes, the migration, or any existing
 * scenario's behavior. See docs/customer-credit/OPERATIONAL_KNOWLEDGE_FRAMEWORK.md.
 */
class ResolutionScenarioRegistry
{
    /**
     * @var array<string, class-string<ResolutionScenario>>
     */
    protected static array $scenarios = [
        CancellationRefundScenario::KEY => CancellationRefundScenario::class,
        ManualResolutionScenario::KEY => ManualResolutionScenario::class,
    ];

    public static function get(string $key): ResolutionScenario
    {
        if (! isset(self::$scenarios[$key])) {
            throw new \InvalidArgumentException("ResolutionScenarioRegistry: no scenario registered for key '{$key}'.");
        }

        return new self::$scenarios[$key]();
    }

    public static function has(string $key): bool
    {
        return isset(self::$scenarios[$key]);
    }

    /**
     * @return ResolutionScenario[]
     */
    public static function all(): array
    {
        return array_map(fn ($class) => new $class(), self::$scenarios);
    }
}

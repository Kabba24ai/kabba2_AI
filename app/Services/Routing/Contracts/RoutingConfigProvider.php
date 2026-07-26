<?php

namespace App\Services\Routing\Contracts;

/**
 * Supplies the routing integration's runtime configuration to the shared
 * routing layer, decoupling it from WHERE the configuration lives. The default
 * binding reads the admin-managed Configurations settings; tests (or a future
 * env-based deployment) can bind a different implementation without touching
 * any provider or consumer.
 */
interface RoutingConfigProvider
{
    /** Whether the operator has switched the integration on. */
    public function enabled(): bool;

    /** Provider key, e.g. 'google'. */
    public function provider(): string;

    /** Plaintext API key, or null when none is stored. Never logged, never sent to the browser. */
    public function apiKey(): ?string;

    /** Prefer traffic-aware durations when the provider supports them. */
    public function trafficAware(): bool;

    /** Per-request timeout in seconds. */
    public function timeout(): int;

    /** Distance unit preference: 'imperial' | 'metric'. */
    public function units(): string;

    /** Enabled AND a usable key present — the single gate the routing service checks. */
    public function isConfigured(): bool;
}

<?php

namespace Tests\Concerns;

use App\Models\Service\ServiceResponsibilityDecision;

/**
 * Resolves a Responsibility Decision master record (seeded by migration and so
 * always present under RefreshDatabase) from its stable key. Lets tests keep
 * referencing the well-known keys (via the ResponsibilityDecision enum's
 * ->value) while the ticket API now takes a master record / its id.
 */
trait ResolvesResponsibilityDecisions
{
    protected function responsibilityDecision(string $key): ServiceResponsibilityDecision
    {
        return ServiceResponsibilityDecision::where('key', $key)->firstOrFail();
    }

    protected function responsibilityDecisionId(string $key): int
    {
        return $this->responsibilityDecision($key)->id;
    }
}

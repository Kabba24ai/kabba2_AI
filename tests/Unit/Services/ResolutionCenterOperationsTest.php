<?php

namespace Tests\Unit\Services;

use App\Services\ResolutionCenterService;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3.6 — Customer Resolution Operations Center.
 *
 * `ResolutionCenterService`'s DB-backed methods (assignCase, setPriority,
 * markWaiting, escalate, dashboardMetrics, and the status-mirroring inside
 * markOutcome/approveAndIssueCredit) require a database and were instead
 * validated live inside a rolled-back transaction, per this project's
 * established precedent (no RefreshDatabase-capable test database exists
 * here — see PHASE_3_6_COMPLETION_REPORT.md). This suite covers only the
 * pure, DB-free validation guards, the same pattern already used by
 * `CustomerCreditServiceTest`.
 */
class ResolutionCenterOperationsTest extends TestCase
{
    public function test_valid_statuses(): void
    {
        $this->assertTrue(ResolutionCenterService::isValidStatus('open'));
        $this->assertTrue(ResolutionCenterService::isValidStatus('in_progress'));
        $this->assertTrue(ResolutionCenterService::isValidStatus('waiting'));
        $this->assertTrue(ResolutionCenterService::isValidStatus('escalated'));
        $this->assertTrue(ResolutionCenterService::isValidStatus('completed'));
        $this->assertFalse(ResolutionCenterService::isValidStatus('bogus'));
    }

    public function test_valid_priorities(): void
    {
        $this->assertTrue(ResolutionCenterService::isValidPriority('low'));
        $this->assertTrue(ResolutionCenterService::isValidPriority('normal'));
        $this->assertTrue(ResolutionCenterService::isValidPriority('high'));
        $this->assertTrue(ResolutionCenterService::isValidPriority('urgent'));
        $this->assertFalse(ResolutionCenterService::isValidPriority('critical'));
    }

    public function test_valid_waiting_on(): void
    {
        $this->assertTrue(ResolutionCenterService::isValidWaitingOn('customer'));
        $this->assertTrue(ResolutionCenterService::isValidWaitingOn('employee'));
        $this->assertFalse(ResolutionCenterService::isValidWaitingOn('manager'));
    }

    public function test_status_constants_are_distinct_from_outcome_constants(): void
    {
        // Guards against ever accidentally reusing an `outcome` value as a
        // `status` value or vice versa — see PHASE_3_6_OPERATIONS_AUDIT.md §5.
        $outcomeValues = [
            ResolutionCenterService::OUTCOME_PENDING,
            ResolutionCenterService::OUTCOME_COMPLETED,
            ResolutionCenterService::OUTCOME_CANCELLED,
        ];
        $statusValues = [
            ResolutionCenterService::STATUS_OPEN,
            ResolutionCenterService::STATUS_IN_PROGRESS,
            ResolutionCenterService::STATUS_WAITING,
            ResolutionCenterService::STATUS_ESCALATED,
            ResolutionCenterService::STATUS_COMPLETED,
        ];

        // 'completed' is the one deliberate overlap (mirrored terminal
        // state) — every other value must be unique to its own field.
        $this->assertSame(['completed'], array_values(array_intersect($outcomeValues, $statusValues)));
    }
}

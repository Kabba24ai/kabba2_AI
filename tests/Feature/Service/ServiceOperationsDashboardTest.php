<?php

namespace Tests\Feature\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\Service\ServiceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOperationsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(array $attrs = []): ServiceTicket
    {
        return ServiceTicket::create(array_merge([
            'service_type'             => ServiceType::InternalRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => FinancialResponsibility::InternalExpense->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'opened_at'                => now()->subDay(),
        ], $attrs));
    }

    public function test_ticket_number_is_auto_generated(): void
    {
        $ticket = $this->makeTicket();
        $this->assertMatchesRegularExpression('/^SVC-\d{5}$/', $ticket->fresh()->ticket_number);
    }

    public function test_blocked_emergency_ticket_is_not_in_active_queue(): void
    {
        $blockedEmergency = $this->makeTicket([
            'priority'      => ServicePriority::Emergency->value,
            'repair_status' => RepairStatus::WaitingOnParts->value,
            'opened_at'     => now()->subDays(14),
        ]);
        $normalActive = $this->makeTicket([
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::InProgress->value,
        ]);

        $activeIds  = ServiceTicket::activeQueue()->pluck('id');
        $blockedIds = ServiceTicket::blockedQueue()->pluck('id');

        $this->assertFalse($activeIds->contains($blockedEmergency->id));
        $this->assertTrue($activeIds->contains($normalActive->id));
        $this->assertTrue($blockedIds->contains($blockedEmergency->id));
    }

    public function test_active_queue_sorts_priority_then_oldest(): void
    {
        $lowOld       = $this->makeTicket(['priority' => ServicePriority::Low->value,       'opened_at' => now()->subDays(30)]);
        $emergencyNew = $this->makeTicket(['priority' => ServicePriority::Emergency->value, 'opened_at' => now()->subHour()]);
        $highOld      = $this->makeTicket(['priority' => ServicePriority::High->value,      'opened_at' => now()->subDays(9)]);
        $highNew      = $this->makeTicket(['priority' => ServicePriority::High->value,      'opened_at' => now()->subDay()]);

        $ordered = ServiceTicket::activeQueue()
            ->orderByRaw(ServicePriority::sqlOrder())
            ->orderBy('opened_at')
            ->pluck('id')
            ->all();

        $this->assertSame([$emergencyNew->id, $highOld->id, $highNew->id, $lowOld->id], $ordered);
    }

    public function test_personnel_relationship_links_hrm_users(): void
    {
        $user = User::create([
            'unique_id'     => 'test-emp-1',
            'employee_code' => '99',
            'first_name'    => 'Test',
            'last_name'     => 'Tech',
            'email'         => 'tech@test.local',
            'status'        => 'Active',
        ]);

        $ticket = $this->makeTicket();
        $ticket->personnel()->sync([$user->id]);

        $this->assertTrue($ticket->fresh()->personnel->contains('id', $user->id));
        $this->assertDatabaseHas('service_ticket_personnel', [
            'service_ticket_id' => $ticket->id,
            'employee_id'       => $user->id,
        ]);
    }

    public function test_overview_page_loads_for_authenticated_user(): void
    {
        $user = User::create([
            'unique_id'     => 'test-emp-2',
            'employee_code' => '98',
            'first_name'    => 'Admin',
            'last_name'     => 'User',
            'email'         => 'admin@test.local',
            'status'        => 'Active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.service-management.overview'))
            ->assertOk()
            ->assertSee('Service Operations')
            ->assertSee('Active Work Queue')
            ->assertSee('Blocked Work Queue')
            ->assertSee('Service Alerts');
    }
}

<?php

namespace Tests\Feature\QueueLine;

use App\Enums\Orders\OrderHistoryActionBy;
use App\Models\Iam\Personnel\User;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\OperationsHistory\OrderOperationsHistory;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineService;

/**
 * Operations History (Enhancement 6/7) — the read-only order-level audit modal.
 * Verifies the four tabs project from the append-only records each workflow
 * already keeps, and that the endpoint renders the modal body.
 */
class OrderOperationsHistoryTest extends QueueLineTestCase
{
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Ops', 'last_name' => 'Tech',
            'email' => 'ops-tech@test.local', 'status' => 'Active',
        ]);
    }

    private function opsUrl($order): string
    {
        return route('admin.order-management.orders.operations-history', $order->unique_id);
    }

    public function test_for_order_populates_all_four_tabs(): void
    {
        $order = $this->makeOrder();
        $row = $this->makeRow($order);
        $unit = $this->softAssign($row);

        // Staging + fuel
        QueueLineService::stage($row->fresh(['softAssignment.equipment']), $this->admin);
        QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $unit,
            performedBy: $this->employee,
            actor: $this->admin,
        );

        // Assignment (a switch)
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $replacement, $this->employee, $this->admin);

        // Dispatch — driver departure timestamps
        $row->forceFill(['delivery_ready_to_go_at' => now()->subMinutes(20), 'delivery_on_my_way_at' => now()->subMinutes(10)])->save();

        // Customer checklist history row
        $order->history()->create([
            'user_id' => $this->admin->id,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => \App\Enums\Orders\OrderHistoryAction::ChecklistDelivered->value,
            'description' => 'Delivery checklist filled and machine delivered.',
        ]);

        $tabs = OrderOperationsHistory::forOrder($order->fresh());

        $this->assertTrue($tabs['staging']->isNotEmpty(), 'staging tab has events');
        $this->assertTrue($tabs['assignment']->isNotEmpty(), 'assignment tab has events');
        $this->assertTrue($tabs['dispatch']->isNotEmpty(), 'dispatch tab has events');
        $this->assertTrue($tabs['checklist']->isNotEmpty(), 'checklist tab has events');

        $this->assertTrue($tabs['dispatch']->contains(fn ($e) => str_contains($e['title'], 'Load Map & Go')));
        $this->assertTrue($tabs['assignment']->contains(fn ($e) => $e['title'] === 'Equipment switched'));
        $this->assertTrue($tabs['staging']->contains(fn ($e) => $e['title'] === 'Fuel Full verified'));
        $this->assertTrue($tabs['checklist']->contains(fn ($e) => str_contains($e['title'], 'Checklist Delivered')));

        // Staging/assignment separation: no assignment titles leak into staging.
        $this->assertFalse($tabs['staging']->contains(fn ($e) => $e['title'] === 'Equipment switched'));
    }

    public function test_endpoint_renders_the_tabbed_modal_body(): void
    {
        $order = $this->makeOrder();
        $row = $this->makeRow($order);
        $this->softAssign($row);
        $row->forceFill(['delivery_on_my_way_at' => now()])->save();

        $this->actingAs($this->admin)
            ->get($this->opsUrl($order))
            ->assertOk()
            ->assertSee('Staging History')
            ->assertSee('Driver Dispatch History')
            ->assertSee('Customer Checklist History')
            ->assertSee('Equipment Assignment')
            ->assertSee('data-ops-tab="staging"', false)
            ->assertSee('Load Map & Go — departed the yard');
    }

    public function test_empty_order_renders_empty_states(): void
    {
        $order = $this->makeOrder();
        $this->makeRow($order); // no staging, dispatch, checklist, or switches

        $tabs = OrderOperationsHistory::forOrder($order->fresh());

        $this->assertTrue($tabs['staging']->isEmpty());
        $this->assertTrue($tabs['dispatch']->isEmpty());
        $this->assertTrue($tabs['checklist']->isEmpty());
        // Assignment tab shows the current reservation only if one exists; here none.
        $this->assertTrue($tabs['assignment']->isEmpty());

        $this->actingAs($this->admin)
            ->get($this->opsUrl($order))
            ->assertOk()
            ->assertSee('No staging history recorded');
    }
}

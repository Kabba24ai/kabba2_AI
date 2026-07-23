<?php
namespace Tests\Feature\OrderManagement;

use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoAssignPageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_and_contains_toggle_and_modals(): void
    {
        $admin = User::create([
            'first_name' => 'R', 'last_name' => 'A',
            'email' => 'render-admin@test.local', 'status' => 'Active',
        ]);

        $res = $this->actingAs($admin)
            ->get(route('admin.order-management.schedule-assignment.index'));

        $res->assertOk();
        $html = $res->getContent();

        foreach ([
            'id="autoAssignToggleBtn"',
            'function toggleAutoAssign()',
            'id="autoAssignEnableModal"',
            'id="autoAssignDisableModal"',
            'function openAutoAssignEnableModal()',
            'function openAutoAssignDisableModal()',
            'All future orders will be assigned to equipment automatically and without admin intervention.',
            'Disabling Auto-Assign will prevent future orders from being assigned to equipment automatically. Enter the Master Password to continue.',
            'id="autoAssignMasterPassword"',
            // CSP-safe: bound by delegation, with data hooks, not inline handlers.
            '__autoAssignEventsBound',
            'data-aa-confirm="disable"',
            'data-aa-close="enable"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "MISSING: {$needle}");
        }

        // No inline event handlers on the Auto-Assign controls — the production
        // CSP blocks them, so they must be bound via addEventListener instead.
        $this->assertStringNotContainsString('onclick="toggleAutoAssign', $html);
        $this->assertStringNotContainsString('onclick="confirmAutoAssign', $html);
        $this->assertStringNotContainsString('onclick="closeAutoAssign', $html);

        // The modals must NOT be nested inside the hidden #scheduleAssistantModal
        // wrapper (a display:none ancestor). They render after it closes, so both
        // modal ids appear after the equipment-store component that follows the
        // scheduling-assistant block.
        $this->assertLessThan(
            strpos($html, 'id="autoAssignEnableModal"'),
            strpos($html, 'id="scheduleAssistantModal"'),
            'Auto-Assign modals must render after (outside) #scheduleAssistantModal',
        );

        // Label renders as "Auto-Assign" and the old label is gone.
        $this->assertStringContainsString('Auto-Assign', $html);
        $this->assertStringNotContainsString('Auto Assign All Orders', $html);
    }
}

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
            'onclick="toggleAutoAssign()"',
            'function toggleAutoAssign()',
            'id="autoAssignEnableModal"',
            'id="autoAssignDisableModal"',
            'function openAutoAssignEnableModal()',
            'function openAutoAssignDisableModal()',
            'All future orders will be assigned to equipment automatically and without admin intervention.',
            'Disabling Auto-Assign will prevent future orders from being assigned to equipment automatically. Enter the Master Password to continue.',
            'id="autoAssignMasterPassword"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "MISSING: {$needle}");
        }

        // Label renders as "Auto-Assign" and the old label is gone.
        $this->assertStringContainsString('Auto-Assign', $html);
        $this->assertStringNotContainsString('Auto Assign All Orders', $html);
    }
}

<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Models\Orders\QueueLineKeyConfirmation;
use App\Services\QueueLine\QueueLineMobilePresenter;
use App\Services\QueueLine\QueueLineReleaseGuard;
use App\Services\QueueLine\QueueLineStagingService;
use Livewire\Livewire;

/**
 * Check applicability (2026-07-21) — staging checks exist only where the
 * physical trait exists: the Fuel check only for units whose Power Source
 * is EXPLICITLY Diesel or Gas; the Key check only for an EXPLICIT 1 Key /
 * 2 Keys starting mechanism. Unset / None / Key Pad / Pull Cord /
 * Batteries / Electric mean the check is not applicable: never required,
 * never recorded, never displayed, and never enforced at release. A plain
 * attachment (forks) stages on the employee sign-off alone.
 */
class QueueLineCheckApplicabilityTest extends QueueLineTestCase
{
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Yard', 'last_name' => 'Tech',
            'email' => 'yard-tech@test.local', 'status' => 'Active',
        ]);
    }

    /** A trait-less attachment: no power source, no starting mechanism. */
    private function makeAttachment(array $overrides = []): Equipment
    {
        return $this->makeEquipment(array_merge([
            'power_source_type' => null,
            'key_starting_mechanism' => null,
        ], $overrides));
    }

    private function stageViaModal(OrderProduct $row, array $set = [])
    {
        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id);

        foreach ($set as $prop => $value) {
            $component->set($prop, $value);
        }

        return $component->call('confirmStaging');
    }

    // ── The trait predicates themselves ──────────────────────────────────

    public function test_the_traits_derive_only_from_explicit_selections(): void
    {
        // Fuel: only explicit Diesel or Gas
        $this->assertTrue($this->makeEquipment(['power_source_type' => 'diesel'])->requiresFuelCheck());
        $this->assertTrue($this->makeEquipment(['power_source_type' => 'gas'])->requiresFuelCheck());
        $this->assertFalse($this->makeEquipment(['power_source_type' => 'batteries'])->requiresFuelCheck());
        $this->assertFalse($this->makeEquipment(['power_source_type' => 'electric'])->requiresFuelCheck());
        $this->assertFalse($this->makeEquipment(['power_source_type' => null])->requiresFuelCheck());

        // Key: only explicit 1 Key or 2 Keys
        $this->assertTrue($this->makeEquipment(['key_starting_mechanism' => '1_key'])->requiresKeyCheck());
        $this->assertTrue($this->makeEquipment(['key_starting_mechanism' => '2_keys'])->requiresKeyCheck());
        $this->assertFalse($this->makeEquipment(['key_starting_mechanism' => 'none'])->requiresKeyCheck());
        $this->assertFalse($this->makeEquipment(['key_starting_mechanism' => 'key_pad'])->requiresKeyCheck());
        $this->assertFalse($this->makeEquipment(['key_starting_mechanism' => 'pull_cord'])->requiresKeyCheck());
        $this->assertFalse($this->makeEquipment(['key_starting_mechanism' => null])->requiresKeyCheck());
    }

    // ── Attachment: neither check ─────────────────────────────────────────

    public function test_an_attachment_stages_on_the_employee_sign_off_alone(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row, $this->makeAttachment());

        // The modal shows neither check, explains why, and enables submit
        // with just the employee selected
        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id);

        $html = $component->html();
        $this->assertStringNotContainsString('data-staging-fuel-check', $html);
        $this->assertStringNotContainsString('data-staging-key-check', $html);
        $this->assertStringContainsString('data-staging-no-checks', $html);

        // Submit enabled with just the employee (ignore the always-present
        // wire:loading token when checking for a real disabled attribute)
        preg_match('/<button[^>]*data-staging-submit[^>]*>/s', $html, $submitTag);
        $this->assertNotEmpty($submitTag);
        $this->assertDoesNotMatchRegularExpression(
            '/\sdisabled(?=[\s>=])/',
            str_replace('wire:loading.attr="disabled"', '', $submitTag[0]),
        );

        $component->call('confirmStaging')->assertSet('stagingItemId', null);

        // Staged with NOTHING recorded in either ledger — the sections
        // simply do not apply
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());

        // Fully staged → the card renders in the Staged section
        $boardHtml = Livewire::test(Board::class)->html();
        $start = strpos($boardHtml, 'data-queue-section="ready"');
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', substr($boardHtml, $start));
    }

    public function test_the_status_dialog_leaves_non_applicable_rows_blank(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row, $this->makeAttachment());
        $this->stageViaModal($row);

        Livewire::test(Board::class)
            ->call('openStagedStatus', $row->id)
            ->assertSee('Staged')
            ->assertDontSee('Not Verified')     // no fuel row rendered at all
            ->assertDontSee('Not Confirmed');   // no key row rendered at all
    }

    public function test_return_to_pending_on_an_attachment_reverses_only_the_latch(): void
    {
        $row = $this->makeRow();
        $unit = $this->makeAttachment();
        $this->softAssign($row, $unit);
        $this->stageViaModal($row);

        Livewire::test(Board::class)->call('returnToPending', $row->id);

        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $this->assertSame(0, QueueLineFuelVerification::count()); // nothing to reverse
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertEquals($unit->id, $row->fresh()->softAssignment->equipment_id);

        // And it can be staged again the same trait-less way
        $this->stageViaModal($row->fresh(['softAssignment.equipment', 'queueLineItem']));
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    // ── Single-trait units ────────────────────────────────────────────────

    public function test_a_pull_cord_gas_unit_requires_fuel_but_never_a_key(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row, $this->makeEquipment([
            'power_source_type' => 'gas', 'key_starting_mechanism' => 'pull_cord',
        ]));

        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id);

        $html = $component->html();
        $this->assertStringContainsString('data-staging-fuel-check', $html);
        $this->assertStringNotContainsString('data-staging-key-check', $html);

        // Fuel still enforced for the applicable check
        $component->set('stagingFuel', 'not_full')->call('confirmStaging')
            ->assertSee('Fuel must be Full');
        $this->assertSame(0, QueueLineFuelVerification::count());

        // Full → staged with a fuel row and NO key row
        $component->set('stagingFuel', 'full')->call('confirmStaging')
            ->assertSet('stagingItemId', null);
        $this->assertSame(1, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    public function test_an_electric_keyed_unit_requires_the_key_but_never_fuel(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row, $this->makeEquipment([
            'power_source_type' => 'electric', 'key_starting_mechanism' => '2_keys',
        ]));

        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id);

        $html = $component->html();
        $this->assertStringNotContainsString('data-staging-fuel-check', $html);
        $this->assertStringContainsString('data-staging-key-check', $html);

        $component->set('stagingKey', 'missing')->call('confirmStaging')
            ->assertSee('key must be with the machine');

        $component->set('stagingKey', 'with_machine')->call('confirmStaging')
            ->assertSet('stagingItemId', null);
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(1, QueueLineKeyConfirmation::count());
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    // ── Release guard ─────────────────────────────────────────────────────

    public function test_release_never_demands_fuel_from_a_unit_that_cannot_have_it(): void
    {
        // Electric unit, staged without fuel — release must be permitted
        $row = $this->makeRow();
        $unit = $this->makeEquipment([
            'power_source_type' => 'electric', 'key_starting_mechanism' => null,
        ]);
        $this->softAssign($row, $unit);

        $this->assertNull(QueueLineReleaseGuard::check($row->fresh(['softAssignment.equipment', 'order', 'queueLineItem'])));

        // A diesel unit without fuel verification is still blocked
        $dieselRow = $this->makeRow();
        $this->softAssign($dieselRow); // default fixture = diesel + 1 key
        $block = QueueLineReleaseGuard::check($dieselRow->fresh(['softAssignment.equipment', 'order', 'queueLineItem']));
        $this->assertSame('QUEUE_FUEL_VERIFICATION_REQUIRED', $block['code']);
    }

    // ── Assignment-capable modal path ─────────────────────────────────────

    public function test_the_checklist_adapts_to_the_selected_replacement_unit(): void
    {
        $category = \App\Models\ProductManagement\ProductCategory::create(['title' => 'Attachments']);
        $row = $this->makeRow();
        $this->softAssign($row); // current = diesel + key
        $attachment = $this->makeAttachment([
            'assigned_product_id' => $row->product_id,
            'product_category_id' => $category->id,
        ]);

        $component = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingMode', 'assign')
            ->set('stagingCategory', (string) $category->id)
            ->set('stagingEquipmentId', (string) $attachment->id)
            ->set('stagingPerformedBy', (string) $this->employee->id);

        // With the trait-less replacement selected, no checks render and
        // the submission assigns + stages in one atomic step
        $html = $component->html();
        $this->assertStringNotContainsString('data-staging-fuel-check', $html);
        $this->assertStringContainsString('data-staging-no-checks', $html);

        $component->call('confirmStaging')->assertSet('stagingItemId', null);

        $this->assertEquals($attachment->id, $row->fresh()->softAssignment->equipment_id);
        $this->assertSame(0, QueueLineFuelVerification::where('action', 'verified')->count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
    }

    // ── Mobile contract ───────────────────────────────────────────────────

    public function test_the_mobile_payload_reports_not_applicable_states(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row, $this->makeAttachment());

        $item = QueueLineMobilePresenter::item($row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']));
        $this->assertSame('not_applicable', $item['fuel']['state']);
        $this->assertSame('not_applicable', $item['key']['state']);
        $this->assertSame('staging_required', $item['readiness']);
        $this->assertTrue($item['available_actions']['mark_staged']);
    }

    public function test_mobile_mark_staged_accepts_an_attachment_without_the_fields(): void
    {
        $admin = $this->admin;
        $row = $this->makeRow();
        $unit = $this->makeAttachment();
        $this->softAssign($row, $unit);

        $response = $this->actingAs($admin, 'api_user')->postJson(
            'http://' . config('app.domains.api') . '/api/admin/v1/queue-line/' . $row->unique_id . '/mark-staged',
            [
                'equipment_unique_id' => $unit->unique_id,
                'performed_by' => $this->employee->unique_id,
                // fuel_full / key_with_machine deliberately omitted —
                // not applicable to this unit
            ],
        );

        $response->assertOk()->assertJsonPath('data.fully_staged', true);
        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);

        // Detail now reads ready (Truck row → ready_for_dispatch)
        $detail = $this->actingAs($admin, 'api_user')->getJson(
            'http://' . config('app.domains.api') . '/api/admin/v1/queue-line/' . $row->unique_id,
        );
        $detail->assertOk()
            ->assertJsonPath('data.readiness', 'ready_for_dispatch')
            ->assertJsonPath('data.fuel.state', 'not_applicable')
            ->assertJsonPath('data.key.state', 'not_applicable');
    }

    public function test_mobile_still_rejects_a_missing_applicable_check(): void
    {
        $row = $this->makeRow();
        $unit = $this->makeEquipment(['power_source_type' => 'diesel', 'key_starting_mechanism' => null]);
        $this->softAssign($row, $unit);

        // Fuel applies but was omitted → rejected, nothing recorded
        $this->actingAs($this->admin, 'api_user')->postJson(
            'http://' . config('app.domains.api') . '/api/admin/v1/queue-line/' . $row->unique_id . '/mark-staged',
            [
                'equipment_unique_id' => $unit->unique_id,
                'performed_by' => $this->employee->unique_id,
            ],
        )->assertUnprocessable()->assertJsonPath('error.code', 'QUEUE_FUEL_NOT_FULL');

        $this->assertSame(0, QueueLineFuelVerification::count());
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->staged_at);
    }
}

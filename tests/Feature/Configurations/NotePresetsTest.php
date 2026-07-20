<?php

namespace Tests\Feature\Configurations;

use App\Models\Dashboard\FuelNotePreset;
use App\Models\Dashboard\ResolutionNotePreset;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Note Presets administration (Billing Engine UI refinement) — the managed
 * lists behind the Billing Engine preset dropdowns. Pins: CRUD for both
 * preset types, uniqueness, unknown-type rejection, and the ordered()
 * scope's sort_order-then-label ordering the dropdowns rely on.
 */
class NotePresetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::create([
            'first_name' => 'Preset', 'last_name' => 'Admin',
            'email' => 'preset-admin@test.local', 'status' => 'Active',
        ]));
    }

    public function test_page_renders_with_both_sections(): void
    {
        FuelNotePreset::create(['label' => 'Called and left a VM']);
        ResolutionNotePreset::create(['label' => 'Waived after review']);

        $this->get(route('admin.configurations.note-presets.index'))
            ->assertOk()
            ->assertSee('Fuel Charge Notes')
            ->assertSee('Resolution Notes')
            ->assertSee('Called and left a VM')
            ->assertSee('Waived after review');
    }

    public function test_full_crud_for_fuel_presets(): void
    {
        $this->postJson(route('admin.configurations.note-presets.store', 'fuel'), [
            'label' => 'Customer denies responsibility', 'sort_order' => 5,
        ])->assertOk()->assertJsonPath('preset.sort_order', 5);

        $preset = FuelNotePreset::firstOrFail();

        $this->putJson(route('admin.configurations.note-presets.update', ['fuel', $preset->id]), [
            'label' => 'Customer denies responsibility — escalated', 'sort_order' => 1,
        ])->assertOk();

        $this->assertSame('Customer denies responsibility — escalated', $preset->fresh()->label);
        $this->assertSame(1, (int) $preset->fresh()->sort_order);

        $this->deleteJson(route('admin.configurations.note-presets.destroy', ['fuel', $preset->id]))
            ->assertOk();
        $this->assertSame(0, FuelNotePreset::count());
    }

    public function test_duplicate_labels_are_rejected(): void
    {
        ResolutionNotePreset::create(['label' => 'Waived']);

        $this->postJson(route('admin.configurations.note-presets.store', 'resolution'), ['label' => 'Waived'])
            ->assertStatus(422);
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->postJson(route('admin.configurations.note-presets.store', 'bogus'), ['label' => 'X'])
            ->assertStatus(404);
    }

    public function test_ordered_scope_sorts_by_sort_order_then_label(): void
    {
        FuelNotePreset::create(['label' => 'Zebra note', 'sort_order' => 0]);
        FuelNotePreset::create(['label' => 'Alpha note', 'sort_order' => 0]);
        FuelNotePreset::create(['label' => 'Last note', 'sort_order' => 9]);

        $this->assertSame(
            ['Alpha note', 'Zebra note', 'Last note'],
            FuelNotePreset::ordered()->pluck('label')->all()
        );
    }
}

<?php

namespace Tests\Feature\OrderManagement;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Auto-Assign toggle is protected by a server-side state-transition rule:
 *
 *   - disabled -> enabled : allowed (confirmation only, no password)
 *   - enabled  -> disabled: the Master Password is REQUIRED and verified
 *   - no-op               : never requires the password
 *
 * Enforcement lives in ToggleAutoAssignController (the single writer of the
 * setting), so it cannot be bypassed by forging the payload or skipping the
 * modal. These tests pin exactly that.
 */
class AutoAssignToggleProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const MASTER = 'CorrectHorse42!';

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'first_name' => 'AA', 'last_name' => 'Admin',
            'email' => 'auto-assign-admin@test.local', 'status' => 'Active',
        ]);

        $this->actingAs($admin);
    }

    // ── Fixtures ──────────────────────────────────────────────────────────

    private function setMasterPassword(string $plain): void
    {
        $s = new Setting();
        $s->setting_type  = 'Admin Settings';
        $s->setting_name  = 'master_password_entry';
        $s->setting_title = 'Master Password - Entry';
        $s->value_type    = 'password';
        $s->is_encrypted  = 1;              // set BEFORE value so the mutator encrypts
        $s->setting_value = $plain;
        $s->save();
    }

    private function setAutoAssign(bool $enabled): void
    {
        $s = Setting::firstOrNew([
            'setting_type' => 'Schedule Assignment',
            'setting_name' => 'auto_assign_enabled',
        ]);
        $s->value_type    = 'boolean';
        $s->setting_title = 'Auto-Assign';
        $s->setting_value = $enabled ? '1' : '0';
        $s->save();
    }

    private function autoAssignValue(): ?string
    {
        return Setting::where('setting_type', 'Schedule Assignment')
            ->where('setting_name', 'auto_assign_enabled')
            ->value('setting_value');
    }

    private function toggle(array $payload)
    {
        return $this->postJson(
            route('admin.order-management.schedule-assignment.toggle-auto-assign'),
            $payload,
        );
    }

    // ── 1. Enable after confirmation ────────────────────────────────────────

    public function test_auto_assign_can_be_enabled_after_confirmation(): void
    {
        // Starts disabled (no row exists yet).
        $this->assertNull($this->autoAssignValue());

        $this->toggle(['enabled' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'enabled' => true, 'changed' => true]);

        $this->assertSame('1', $this->autoAssignValue());
    }

    // ── 2. Enabling never requires the Master Password ──────────────────────

    public function test_enabling_does_not_require_the_master_password(): void
    {
        // No Master Password configured at all, no auto-assign row → still enables.
        $this->toggle(['enabled' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'enabled' => true]);

        $this->assertSame('1', $this->autoAssignValue());
    }

    // ── 3. Enabled cannot be disabled without a password ────────────────────

    public function test_enabled_cannot_be_disabled_without_a_password(): void
    {
        $this->setMasterPassword(self::MASTER);
        $this->setAutoAssign(true);

        $this->toggle(['enabled' => false])   // no master_password supplied
            ->assertStatus(422)
            ->assertJsonValidationErrors('master_password');

        // State unchanged.
        $this->assertSame('1', $this->autoAssignValue());
    }

    // ── 4. Incorrect Master Password cannot disable ─────────────────────────

    public function test_incorrect_master_password_cannot_disable(): void
    {
        $this->setMasterPassword(self::MASTER);
        $this->setAutoAssign(true);

        $this->toggle(['enabled' => false, 'master_password' => 'not-the-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('master_password');

        $this->assertSame('1', $this->autoAssignValue());
    }

    // ── 5. Correct Master Password disables ─────────────────────────────────

    public function test_correct_master_password_disables(): void
    {
        $this->setMasterPassword(self::MASTER);
        $this->setAutoAssign(true);

        $response = $this->toggle(['enabled' => false, 'master_password' => self::MASTER])
            ->assertOk()
            ->assertJson(['success' => true, 'enabled' => false, 'changed' => true]);

        $this->assertSame('0', $this->autoAssignValue());

        // The stored password is never echoed back to the client.
        $this->assertStringNotContainsString(self::MASTER, $response->getContent());
    }

    // ── 6. A forged direct request cannot bypass the password ───────────────

    public function test_forged_direct_request_cannot_bypass_password(): void
    {
        $this->setMasterPassword(self::MASTER);
        $this->setAutoAssign(true);

        // Hitting the endpoint directly (skipping the modal) with a forged
        // disabled value and no/empty password must not disable.
        $this->toggle(['enabled' => false, 'master_password' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('master_password');
        $this->assertSame('1', $this->autoAssignValue());

        // A string-forged false likewise cannot slip through.
        $this->toggle(['enabled' => 'false'])
            ->assertStatus(422);
        $this->assertSame('1', $this->autoAssignValue());
    }

    // ── 7. A no-op request never requires the password ──────────────────────

    public function test_noop_request_does_not_require_the_password(): void
    {
        // Already disabled → requesting disabled is a no-op, no password needed.
        $this->setAutoAssign(false);
        $this->toggle(['enabled' => false])
            ->assertOk()
            ->assertJson(['success' => true, 'enabled' => false, 'changed' => false]);
        $this->assertSame('0', $this->autoAssignValue());

        // Already enabled → requesting enabled is a no-op, no password needed.
        $this->setMasterPassword(self::MASTER);
        $this->setAutoAssign(true);
        $this->toggle(['enabled' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'enabled' => true, 'changed' => false]);
        $this->assertSame('1', $this->autoAssignValue());
    }

    // ── 8. A failed attempt leaves the persisted state unchanged ────────────

    public function test_failed_attempt_leaves_state_unchanged_then_correct_password_succeeds(): void
    {
        $this->setMasterPassword(self::MASTER);
        $this->setAutoAssign(true);

        // Simulated cancel/failure path: wrong password.
        $this->toggle(['enabled' => false, 'master_password' => 'wrong'])
            ->assertStatus(422);
        $this->assertSame('1', $this->autoAssignValue());

        // The correct password (retry inside the still-open modal) then works.
        $this->toggle(['enabled' => false, 'master_password' => self::MASTER])
            ->assertOk();
        $this->assertSame('0', $this->autoAssignValue());
    }
}

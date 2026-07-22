<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sets the Auto-Assign flag. The desired target state is supplied by the
 * client, but the server decides what protection applies purely from the
 * PERSISTED state — never from anything the payload can forge:
 *
 *   - already at the requested value  -> no-op, no Master Password
 *   - disabled -> enabled             -> allowed, no Master Password
 *   - enabled  -> disabled            -> Master Password REQUIRED & verified here
 *
 * This is the single writer of `auto_assign_enabled`, so gating the
 * Enabled -> Disabled transition here cannot be bypassed by calling the
 * endpoint directly, editing the checkbox, forging the payload, or skipping
 * the modal.
 */
class ToggleAutoAssignController extends Controller
{
    const SETTING_TYPE = 'Schedule Assignment';
    const SETTING_NAME = 'auto_assign_enabled';

    public function __invoke(Request $request): JsonResponse
    {
        $setting = Setting::firstOrNew([
            'setting_type' => self::SETTING_TYPE,
            'setting_name' => self::SETTING_NAME,
        ]);

        $currentlyEnabled = $setting->exists && $setting->setting_value === '1';
        $desiredEnabled   = $request->boolean('enabled');

        // No-op — the request does not change the persisted state. Never needs
        // the Master Password (covers "already disabled" and "already enabled").
        if ($currentlyEnabled === $desiredEnabled) {
            return response()->json([
                'success' => true,
                'enabled' => $currentlyEnabled,
                'changed' => false,
            ]);
        }

        // Enabling (disabled -> enabled): confirmation only, no password.
        if ($desiredEnabled) {
            $this->persist($setting, true);

            return response()->json([
                'success' => true,
                'enabled' => true,
                'changed' => true,
            ]);
        }

        // Disabling (enabled -> disabled): the Master Password is required and
        // verified server-side against the existing encrypted setting.
        if (! $this->masterPasswordMatches((string) $request->input('master_password', ''))) {
            return response()->json([
                'success' => false,
                'message' => 'The Master Password is incorrect.',
                'errors'  => [
                    'master_password' => ['The Master Password is incorrect.'],
                ],
            ], 422);
        }

        $this->persist($setting, false);

        return response()->json([
            'success' => true,
            'enabled' => false,
            'changed' => true,
        ]);
    }

    private function persist(Setting $setting, bool $enabled): void
    {
        if (! $setting->exists) {
            $setting->value_type    = 'boolean';
            $setting->setting_title = 'Auto-Assign';
        }

        $setting->setting_value = $enabled ? '1' : '0';
        $setting->save();
    }

    /**
     * Verify an entered password against the stored, encrypted Master Password
     * (`master_password_entry`) using the app's existing reversible-crypt
     * setting mechanism plus a timing-safe comparison. The stored password is
     * never returned, decrypted to the client, or logged.
     */
    private function masterPasswordMatches(string $entered): bool
    {
        if ($entered === '') {
            return false;
        }

        $master = Setting::where('setting_name', 'master_password_entry')->first();
        $stored = $master?->getDecryptedSettingValue();

        if ($stored === null || $stored === '') {
            return false;
        }

        return hash_equals($stored, $entered);
    }
}

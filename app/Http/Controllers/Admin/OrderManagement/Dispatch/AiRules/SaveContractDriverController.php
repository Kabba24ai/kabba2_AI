<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Create / edit an external contract driver.
 *
 * A contract driver is a users row flagged is_contract_driver = true (and
 * is_driver = true, so it is assignable everywhere in Dispatch) but excluded from
 * the HRM employee list. Dispatch-only for now: a placeholder email + random
 * password are generated so the row is valid; no mobile-login onboarding. The
 * mission designation lives on the AI driver-capability row.
 */
class SaveContractDriverController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'id'           => 'nullable|exists:users,id',
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'nullable|string|max:100',
            'mobile_phone' => 'nullable|string|max:30',
            'cdl_a'        => 'boolean',
            'cdl_b'        => 'boolean',
            'designation'  => 'required|in:primary,secondary,alternate',
        ]);

        $cdlA = $request->boolean('cdl_a');
        $cdlB = $request->boolean('cdl_b');

        if (!empty($data['id'])) {
            // Only ever edit rows that are actually contract drivers.
            $user = User::where('id', $data['id'])->where('is_contract_driver', true)->firstOrFail();
            $user->update([
                'first_name'   => $data['first_name'],
                'last_name'    => $data['last_name'] ?? '',
                'mobile_phone' => $data['mobile_phone'] ?? null,
                'cdl_a'        => $cdlA,
                'cdl_b'        => $cdlB,
            ]);
        } else {
            $user = User::create([
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'] ?? '',
                'mobile_phone'       => $data['mobile_phone'] ?? null,
                'email'              => 'contractor+' . Str::uuid() . '@contractor.local',
                'password'           => Str::random(40), // hashed by the model cast; login not provisioned
                'status'             => 'Active',
                'is_driver'          => true,
                'is_contract_driver' => true,
                'cdl_a'              => $cdlA,
                'cdl_b'              => $cdlB,
            ]);
        }

        DispatchAiDriverCapability::updateOrCreate(
            ['user_id' => $user->id],
            ['designation' => $data['designation'], 'cdl_license' => $cdlA || $cdlB],
        );

        return redirect()
            ->route('admin.order-management.dispatch.ai-rules.index', ['tab' => 'contract_drivers'])
            ->with('success', 'Contract driver saved.');
    }
}

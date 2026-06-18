<?php

namespace App\Http\Controllers\Admin\Hrm\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Dispatch\DispatchAiDriverCapability;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\EmergencyContact;
use App\Helpers\CustomHelper;
use App\Http\Requests\Admin\Hrm\Users\UpdateRequest;
use App\Models\Iam\AccessControl\Role;
use Hash;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;


class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateRequest $request, $unique_id)
    {

        // Find the user by unique_id
        $user = User::where('unique_id', $unique_id)->firstOrFail();

        $validated = $request->validated();

        // dd($validated);

        DB::beginTransaction();

        try {
            $userData = [
                'first_name'     => $validated['first_name'],
                'middle_name'    => $validated['middle_name'] ?? null,
                'last_name'      => $validated['last_name'],
                'email'          => $validated['email'],
                'mobile_phone'   => $validated['mobile_phone'] ?? null,
                'phone_number'   => $validated['phone_number'] ?? null,
                'street_address' => $validated['street'] ?? null,
                'city'           => $validated['city'] ?? null,
                'state'          => $validated['state'] ?? null,
                'zip_code'       => $validated['zip'] ?? null,
                'country'        => $validated['country'] ?? null,
                
                'start_date'     => CustomHelper::parseDateFromInput($validated['startDate'] ?? null),
                'end_date'       => CustomHelper::parseDateFromInput($validated['endDate'] ?? null),
                'pay_type'       => $validated['payType'],

                'status'         => $validated['status'],

                'store_id' => $validated['store_id'] ?? null,

                'limit_start_time' => $validated['limit_start'] ?? false,
                'limit_end_time'   => $validated['limit_end'] ?? false,
                'lunch_override'   => $validated['lunch_override'] ?? false,

                'is_driver' => !empty($validated['is_driver']),
                'cdl_a'     => !empty($validated['cdl_a']),
                'cdl_b'     => !empty($validated['cdl_b']),


                'auto_clockout_penalty' => $validated['auto_clockout_penalty'] ?? null,

                'shift_start_time' => !empty($validated['shift_start_time'])
        ? Carbon::createFromFormat('h:i A', $validated['shift_start_time'])->format('H:i')
        : null,

    'shift_end_time' => !empty($validated['shift_end_time'])
        ? Carbon::createFromFormat('h:i A', $validated['shift_end_time'])->format('H:i')
        : null,
            ];

            // Only update password if provided
            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            //    'social_security'        => $validated['social_security'] ?? null,

            if (!empty($validated['social_security']) &&
                $validated['social_security'] !== 'xxx-xx-xxx') {

                $userData['social_security'] = Crypt::encryptString(
                    $validated['social_security']
                );
            }

            // Update User
            $user->update($userData);


            // Sync user roles
            if (isset($validated['roles']) && is_array($validated['roles'])) {
           $roles = Role::whereIn('id', $validated['roles'])->get();
    $user->syncRoles($roles);
            } else {
                $user->syncRoles([]); // Remove all roles if none are selected
            }



            // Emergency Contact 1
            if ($validated['emergency_first_name'] ?? false) {
                $user->emergencyContacts()->updateOrCreate(
                    ['contact_index' => 1],
                    [
                        'first_name'     => $validated['emergency_first_name'],
                        'middle_name'    => $validated['emergency_middle_name'] ?? null,
                        'last_name'      => $validated['emergency_last_name'] ?? null,
                        'email'          => $validated['emergency_email'] ?? null,
                        'mobile_phone'   => $validated['emergency_mobile_phone'] ?? null,
                        'phone_number'   => $validated['emergency_phone'] ?? null,
                        'street_address' => $validated['emergency_address'] ?? null,
                        'city'           => $validated['emergency_city'] ?? null,
                        'state'          => $validated['emergency_state'] ?? null,
                        'zip_code'       => $validated['emergency_zip'] ?? null,
                        'country'        => $validated['emergency_country'] ?? null,
                    ]
                );
            }

            // Emergency Contact 2
            if ($validated['emergency2_first_name'] ?? false) {
                $user->emergencyContacts()->updateOrCreate(
                    ['contact_index' => 2],
                    [
                        'first_name'     => $validated['emergency2_first_name'],
                        'middle_name'    => $validated['emergency2_middle_name'] ?? null,
                        'last_name'      => $validated['emergency2_last_name'] ?? null,
                        'email'          => $validated['emergency2_email'] ?? null,
                        'mobile_phone'   => $validated['emergency2_mobile_phone'] ?? null,
                        'phone_number'   => $validated['emergency2_phone_number'] ?? null,
                        'street_address' => $validated['emergency2_street_address'] ?? null,
                        'city'           => $validated['emergency2_city'] ?? null,
                        'state'          => $validated['emergency2_state'] ?? null,
                        'zip_code'       => $validated['emergency2_zip'] ?? null,
                        'country'        => $validated['emergency2_country'] ?? null,
                    ]
                );
            }

            // Sync CDL license to Dispatch capabilities (HRM is source of truth)
            if ($user->is_driver) {
                DispatchAiDriverCapability::where('user_id', $user->id)
                    ->update(['cdl_license' => (bool) $user->cdl_a || (bool) $user->cdl_b]);
            }

            DB::commit();

            flash('User updated successfully.')->success();
            return redirect()->route('admin.hrm.users.index');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while updating the user.')->error();
            return back()->withInput()->withErrors(['error' => 'An error occurred while updating the user.']);
        }
    }
}

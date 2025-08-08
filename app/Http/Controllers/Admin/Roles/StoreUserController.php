<?php

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\EmergencyContact;
use App\Helpers\ModelHelper;
use App\Helpers\CustomHelper;
use App\Http\Requests\Admin\Roles\StoreRequest;
use App\Models\Iam\AccessControl\Role;

class StoreUserController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $userData = [
                'first_name'     => $validated['first_name'] ?? null,
                'middle_name'    => $validated['middle_name'] ?? null,
                'last_name'      => $validated['last_name'] ?? null,
                'email'          => $validated['email'] ?? null,
                'mobile_phone'   => $validated['mobile_phone'] ?? null,
                'phone_number'   => $validated['phone_number'] ?? null,
                'street_address' => $validated['street'] ?? null,
                'city'           => $validated['city'] ?? null,
                'state'          => $validated['state'] ?? null,
                'zip_code'       => $validated['zip'] ?? null,
                'country'        => $validated['country'] ?? null,
                'start_date' => CustomHelper::parseDateFromInput($validated['startDate'] ?? null),
                'end_date' => CustomHelper::parseDateFromInput($validated['endDate'] ?? null),
                'pay_type'       => $validated['payType'] ?? null,
                'clock_code'     => $validated['clockCode'] ?? null,
                'status'         => $validated['status'] ?? 'Active',
              
                'limit_start_time' => $validated['limit_start'] ?? false,

                'limit_end_time' => $validated['limit_end'] ?? false,


            ];

            // Create User
            $user = User::create($userData);

            // Assign selected roles
            if (!empty($validated['roles']) && is_array($validated['roles'])) {
                $user->syncRoles(Role::whereIn('id', $validated['roles'])->get());
            }

            // Emergency Contact 1
            if ($validated['emergency_first_name'] ?? false) {
                $user->emergencyContacts()->create([
                    'contact_index'  => 1,
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
                ]);
            }

            // Emergency Contact 2
            if ($validated['emergency2_first_name'] ?? false) {
                $user->emergencyContacts()->create([
                    'contact_index'  => 2,
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
                ]);
            }

            DB::commit();

            flash('User created successfully.')->success();

            return redirect()->route('admin.roles.index');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while creating the user.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the user.']);
        }
    }
}

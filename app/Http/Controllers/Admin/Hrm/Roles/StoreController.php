<?php

namespace App\Http\Controllers\Admin\Hrm\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\EmergencyContact;
use App\Helpers\ModelHelper;
use App\Helpers\CustomHelper;
use App\Http\Requests\Admin\Hrm\Roles\StoreRequest;
use App\Models\Iam\AccessControl\Role;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {

        $validated = $request->validated();

        DB::beginTransaction();

        try {
           
            DB::commit();


            // Create role
        $role = Role::create([
            'name' => $validated['role_name'] ?? null,
            'color' => $validated['color'] ?? null, 
            'description' => $validated['description'] ?? null, 
            'guard_name' => 'web', // or 'admin' based on your setup
        ]);


            flash('User created successfully.')->success();

            return redirect()->route('admin.hrm.roles.manage');

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

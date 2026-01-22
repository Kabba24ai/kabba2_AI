<?php

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\EmergencyContact;
use App\Helpers\ModelHelper;
use App\Helpers\CustomHelper;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Models\Iam\AccessControl\Role;

class StoreRoleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRoleRequest $request)
    {

        $validated = $request->validated();

        DB::beginTransaction();

        try {
           
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

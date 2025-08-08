<?php
namespace App\Http\Controllers\Admin\Hrm\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\EmergencyContact;
use App\Helpers\ModelHelper;
use App\Helpers\CustomHelper;
use App\Http\Requests\Admin\Hrm\Roles\UpdateRoleRequest;
use App\Models\Iam\AccessControl\Role;

class UpdateRoleController extends Controller
{
    public function __invoke(UpdateRoleRequest $request, $unique_id)
    {
        $role = Role::where('unique_id', $unique_id)->firstOrFail();

         $validated = $request->validated();


        DB::beginTransaction();

        try {
            // Update basic fields
            $role->update([
                'name' => $validated['role_name'] ?? null,
                'color' => $validated['color'] ?? null, 
                'description' => $validated['description'] ?? null, 
            ]);


            DB::commit();

            return redirect()->route('admin.hrm.roles.manage.role')
                ->with('success', 'Role updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update role: ' . $e->getMessage()]);
        }
    }
}

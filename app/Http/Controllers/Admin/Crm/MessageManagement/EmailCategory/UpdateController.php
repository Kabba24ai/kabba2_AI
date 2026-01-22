<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\EmailCategory;
use App\Http\Requests\Admin\Crm\MessageManagement\EmailCategory\UpdateRequest;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $cat = EmailCategory::where('unique_id', $unique_id)->firstOrFail();

            $cat->update([
                'name' => $validated['email_cat_name'],
                'description' => $validated['email_cat_description'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Email category updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Update failed.',
            ], 500);
        }
    }
}

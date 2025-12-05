<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\SmsCategory;
use App\Http\Requests\Admin\Crm\MessageManagement\SmsCategory\UpdateRequest;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $cat = SmsCategory::where('unique_id', $unique_id)->firstOrFail();

            $cat->update([
                'name' => $validated['sms_cat_name'],
                'description' => $validated['sms_cat_description'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Sms category updated successfully.',
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

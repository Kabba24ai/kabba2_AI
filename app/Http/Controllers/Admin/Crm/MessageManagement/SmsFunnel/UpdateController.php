<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Crm\MessageManagement\SmsFunnel\StoreRequest;

use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    public function __invoke(StoreRequest $request, $id)
    {
      $validated = $request->validated();
      
        DB::beginTransaction();

        try {
            $funnel = SmsFunnel::findOrFail($id);

            $funnel->update([
                'sms_cat_id'  => $validated['sms_funnel_cat_id'],
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'SMS Funnel updated successfully!',
                'funnel'  => $funnel
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Update failed'
            ], 500);
        }
    }
}

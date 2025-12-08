<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\SmsBroadcast;
use App\Http\Requests\Admin\Crm\MessageManagement\SmsBroadcast\StoreRequest;

class UpdateController extends Controller
{
    public function __invoke(StoreRequest $request, $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Find the broadcast
            $broadcast = SmsBroadcast::findOrFail($id);

            // Update fields
            $broadcast->update([
                'sms_cat_id'  => $validated['sms_cat_id'],
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();

            // Return updated broadcast data
            return response()->json([
                'status'    => 'success',
                'message'   => 'SMS Broadcast updated successfully.',
                'broadcast' => [
                    'id'          => $broadcast->id,
                    'name'        => $broadcast->name,
                    'category'    => $broadcast->category->name ?? 'No Category',
                    'category_id' => $broadcast->sms_cat_id,
                    'description' => $broadcast->description,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Update failed. Please try again.',
            ], 500);
        }
    }
}

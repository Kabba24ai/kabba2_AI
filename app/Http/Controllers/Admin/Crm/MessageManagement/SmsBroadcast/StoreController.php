<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\SmsBroadcast;
use App\Http\Requests\Admin\Crm\MessageManagement\SmsBroadcast\StoreRequest;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            //  IMPORTANT: capture the created broadcast
            $broadcast = SmsBroadcast::create([
                'sms_cat_id'  => $validated['sms_cat_id'],
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Sms Broadcast created successfully.',
                'broadcast' => [
                    'id' => $broadcast->id,
                    'name' => $broadcast->name,
                    'category' => $broadcast->category->name ?? 'No Category',
                    'description' => $broadcast->description,
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create Broadcast. Please try again.',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Crm\MessageManagement\SmsFunnel\StoreRequest;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // $funnel = SmsFunnel::create($validated);

            $funnel = SmsFunnel::create([
                'sms_cat_id'  => $validated['sms_funnel_cat_id'],
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Sms Funnel created successfully.',
                'funnel' => [
                    'id' => $funnel->id,
                    'name' => $funnel->name,
                    'category' => $funnel->category->name ?? 'No Category',
                    'description' => $funnel->description,
                ]
            ]);
        } catch (\Exception $e) {
            report($e);

            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create funnel'
            ], 500);
        }
    }
}

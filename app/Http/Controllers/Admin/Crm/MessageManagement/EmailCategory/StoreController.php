<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customers\EmailCategory;
use App\Http\Requests\Admin\Crm\MessageManagement\EmailCategory\StoreRequest;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {

            EmailCategory::create([
              
                'name'        => $validated['email_cat_name'],
                'description' => $validated['email_cat_description'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Email category created successfully.',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create category. Please try again.',
                'error'   => $e->getMessage(), // optional for debugging
            ], 500);
        }
    }
}

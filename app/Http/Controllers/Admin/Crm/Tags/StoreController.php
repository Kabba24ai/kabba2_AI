<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag\StoreRequest;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Customers\Tag;


class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        $tag = Tag::create([
            'name' =>   $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully!',
            'tag' => $tag,
        ]);

    }
}

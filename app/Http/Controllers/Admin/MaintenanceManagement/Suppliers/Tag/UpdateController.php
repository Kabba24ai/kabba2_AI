<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\SupplierTag;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag\StoreRequest;

class UpdateController extends Controller
{
    public function __invoke(StoreRequest $request, SupplierTag $tag)
    {
        $data = $request->validated();

        $tag->update([
            'name' => $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully!',
            'tag' => $tag,
        ]);
    }
}

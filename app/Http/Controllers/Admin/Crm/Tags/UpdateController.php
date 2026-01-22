<?php

namespace App\Http\Controllers\Admin\Crm\Tags;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Tag;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag\UpdateRequest;


class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Tag $tag)
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

<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;
use Illuminate\Http\Request;

class UpdateTagsController extends Controller
{
    public function __invoke(Request $request, Supplier $supplier)
    {
        $request->validate([
            'tags'   => ['nullable', 'array'],
            'tags.*' => ['integer'],
        ]);

        $supplier->update([
            'tags' => $request->filled('tags') ? implode(',', $request->tags) : null,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Tags updated successfully.',
            'tag_objects' => $supplier->tag_objects->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->values(),
        ]);
    }
}

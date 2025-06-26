<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Templates\UpdateRequest;
use App\Models\Template;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Template $unique_id)
    {
        $unique_id->update($request->validated());

        // Sync parts with sort order
        $partsWithOrder = [];
        if ($request->filled('parts')) {
            foreach ($request->parts as $index => $partId) {
                $partsWithOrder[$partId] = ['sort_order' => $index + 1];
            }
        }
        
        $unique_id->parts()->sync($partsWithOrder);

        return redirect()
            ->route('admin.templates.index')
            ->with('success', 'Template updated successfully.');
    }
}

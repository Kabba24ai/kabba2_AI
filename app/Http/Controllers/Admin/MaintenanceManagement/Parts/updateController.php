<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\UpdateRequest;
use App\Models\MaintenanceManagement\Part;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Part $unique_id)
    {
        $unique_id->update($request->validated());

        return redirect()
            ->route('admin.maintenance-management.parts.index')
            ->with('success', 'Part updated successfully.');
    }
}


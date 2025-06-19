<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\StoreRequest;
use App\Models\Part\Part;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $part = Part::create($request->validated());

        return redirect()
            ->route('admin.maintenance-management.parts.index')
            ->with('success', 'Part created successfully.');
    }
}

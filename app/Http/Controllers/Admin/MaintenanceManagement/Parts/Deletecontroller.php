<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\Part\Part;

class DeleteController extends Controller
{
    public function __invoke(Part $unique_id)
    {
        $partName = $unique_id->part_name;
        $unique_id->delete();

        return redirect()
            ->route('admin.maintenance-management.parts.index')
            ->with('success', "Part \"{$partName}\" has been deleted.");
    }
}

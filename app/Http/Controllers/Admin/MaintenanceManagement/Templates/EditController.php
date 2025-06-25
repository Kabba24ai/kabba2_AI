<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Template;
use App\Models\MaintenanceManagement\Part;

class EditController extends Controller
{
    public function __invoke(Template $unique_id)
    {
        $template = $unique_id->load('parts');
        
        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];
        $allParts = Part::orderBy('part_name')->get();
        
        // Get parts not in template
        $templatePartIds = $template->parts->pluck('id')->toArray();
        $availableParts = $allParts->whereNotIn('id', $templatePartIds);

        return view('admin.templates.edit', compact('template', 'categories', 'allParts', 'availableParts'));
    }
}

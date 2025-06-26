<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Models\Template;

class DeleteController extends Controller
{
    public function __invoke(Template $unique_id)
    {
        $templateName = $unique_id->name;
        $unique_id->delete();

        return redirect()
            ->route('admin.templates.index')
            ->with('success', "Template \"{$templateName}\" has been deleted.");
    }
}

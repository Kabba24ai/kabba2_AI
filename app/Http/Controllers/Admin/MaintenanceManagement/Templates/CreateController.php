<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Models\Part\Part;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];
        $parts = Part::orderBy('part_name')->get();

        return view('admin.templates.create', compact('categories', 'parts'));
    }
}

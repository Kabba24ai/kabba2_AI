<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Template;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Template::withCount('parts')->orderBy('name');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('created_by', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $templates = $query->get();

        $categories = ['Bulldozers', 'Compressors', 'Excavators', 'Generators', 'Loaders', 'Supplies'];

        return view('admin.templates.index', compact('templates', 'categories'));
    }
}

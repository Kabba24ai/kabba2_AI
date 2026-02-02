<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;


class IndexController extends Controller
{
    public function __invoke()
    {
        // $checklistMasters = ChecklistMaster::with('category', 'rentalReadyTemplate', 'customerAdminTemplate')->orderBy('checklist_system_name', 'asc')->get();

        $equipmentCategories = ProductCategory::getHierarchy();

$query = ChecklistMaster::with('category', 'rentalReadyTemplate', 'customerAdminTemplate')
    ->orderBy('checklist_system_name', 'asc');

if ($search = request('search')) {
    $query->where('checklist_system_name', 'like', "%{$search}%");
}

if ($category = request('categoryFilter')) {
    $query->where('equipment_category_id', $category);
}

$perPage = request('per_page', 30);
$perPageVal = $perPage === 'all' ? $query->count() : (int) $perPage;

$checklistMasters = $query->paginate($perPageVal)->withQueryString();


// AJAX Response
if (request()->ajax()) {
    return response()->json([
        'html' => view('admin.checklist_management.checklist_master.partials._table', compact('checklistMasters'))->render(),
        'total' => $checklistMasters->total()
    ]);
}


        // Return the view with the settings data
        return view('admin.checklist_management.checklist_master.index',compact('checklistMasters', 'equipmentCategories')  );
    }
}

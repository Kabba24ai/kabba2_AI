<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\IntervalPreset;
use App\Models\MaintenanceManagement\ServiceMasterSettings;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'tasks');
        
        // Load data based on tab or AJAX request
        $tasks = ServiceTask::with('category')->get();
        $categories = ServiceCategory::all();
        $templates = ServiceTemplate::with(['preset', 'templateTasks.task.category'])->get();
        $presets = IntervalPreset::all();
        $settings = ServiceMasterSettings::first();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'tasks' => $tasks,
                'categories' => $categories,
                'templates' => $templates,
                'presets' => $presets,
                'settings' => $settings
            ]);
        }
        
        return view('admin.maintenance_management.service_master.index', compact(
            'activeTab',
            'tasks',
            'categories', 
            'templates',
            'presets',
            'settings'
        ));
    }
}
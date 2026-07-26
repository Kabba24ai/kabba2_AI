<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Enums\Service\ApprovalType;
use App\Enums\Service\FinancialResponsibility;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\IntervalPreset;
use App\Models\MaintenanceManagement\ServiceMasterSettings;
use App\Models\Service\ServiceResponsibilityDecision;
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

        // Responsibility Decisions master data + the enforced-mapping option
        // lists (financial disposition + approval routing) for the editor.
        $responsibilityDecisions = ServiceResponsibilityDecision::withCount('tickets')->ordered()->get();
        $financialPathOptions = collect(FinancialResponsibility::cases())
            ->reject(fn ($case) => $case === FinancialResponsibility::Pending)
            ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()])
            ->values();
        $approvalTypeOptions = collect(ApprovalType::cases())
            ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()])
            ->values();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'tasks' => $tasks,
                'categories' => $categories,
                'templates' => $templates,
                'presets' => $presets,
                'settings' => $settings,
                'responsibilityDecisions' => $responsibilityDecisions,
            ]);
        }

        return view('admin.maintenance_management.service_master.index', compact(
            'activeTab',
            'tasks',
            'categories',
            'templates',
            'presets',
            'settings',
            'responsibilityDecisions',
            'financialPathOptions',
            'approvalTypeOptions'
        ));
    }
}
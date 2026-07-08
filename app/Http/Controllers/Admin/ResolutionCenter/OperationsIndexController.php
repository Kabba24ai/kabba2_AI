<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Models\Customers\ResolutionCase;
use App\Models\Iam\Personnel\User;
use App\Models\Stores\Store;
use App\Services\ResolutionCenter\ResolutionScenarioRegistry;
use App\Services\ResolutionCenterService;
use Illuminate\Http\Request;

/**
 * Phase 3.6 — Customer Resolution Operations Center.
 *
 * The operational work queue — every active/recent Resolution Case,
 * filterable and paginated. Follows the exact AJAX-partial-refresh
 * convention already used by Dispatch and CRM Customers (see
 * PHASE_3_6_OPERATIONS_AUDIT.md §3), not Schedule Conflicts' full-page-GET
 * convention or a new kanban board this codebase has no precedent for.
 *
 * Dashboard metrics are computed on every request (both the initial full
 * page load and each AJAX filter refresh) — cheap aggregate counts, not
 * historical reporting, per this phase's explicit scope boundary.
 */
class OperationsIndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $metrics = ResolutionCenterService::dashboardMetrics();

        if ($request->ajax()) {
            $query = ResolutionCase::with(['customer', 'order', 'assignedTo', 'responsiblePerson', 'store']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('assigned_to')) {
                $query->where('assigned_to_user_id', $request->assigned_to);
            }
            if ($request->filled('scenario_key')) {
                $query->where('scenario_key', $request->scenario_key);
            }
            if ($request->filled('issue_category')) {
                $query->where('issue_category', $request->issue_category);
            }
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }
            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = (int) $request->input('per_page', 25);
            $cases = $query->latest('id')->paginate($perPage)->withQueryString();

            $tableView = view('admin.resolution_center.operations.partials._table', compact('cases'))->render();

            return response()->json([
                'html' => $tableView,
                'total' => $cases->total(),
            ]);
        }

        return view('admin.resolution_center.operations.index', [
            'metrics' => $metrics,
            'scenarios' => ResolutionScenarioRegistry::all(),
            'employees' => User::active()->orderBy('first_name')->get(),
            'stores' => Store::active()->orderBy('store_name')->get(),
            'issueCategories' => \App\Services\ResolutionCenter\ManualResolutionScenario::CATEGORY_LABELS,
        ]);
    }
}

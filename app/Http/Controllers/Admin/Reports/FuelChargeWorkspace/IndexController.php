<?php

namespace App\Http\Controllers\Admin\Reports\FuelChargeWorkspace;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Dashboard\FuelNotePreset;
use App\Models\Dashboard\ResolutionNotePreset;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Services\Alerts\ChargeAlertQueue;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Dashboard V2 Phase 1A — Fuel Charge Workspace.
 *
 * The ACTIVE queue is the canonical ChargeAlertQueue::fuelAlerts()
 * collection — the exact dataset behind the dashboard card's Outstanding
 * count — with workspace filters applied in memory ON TOP of it, so the
 * unfiltered workspace count always equals the dashboard count. Terminal
 * statuses (resolved/completed/uncollectible) are historical lookups and
 * use a direct query instead (they are not part of the reconciled queue).
 *
 * This controller is read-only: every action (notes, adjust, payment,
 * resolve, uncollectible, history) posts to the pre-existing canonical
 * dashboard endpoints — no new business logic lives here.
 */
class IndexController extends Controller
{
    private const PER_PAGE = 15;

    public function __invoke(Request $request)
    {
        $status = $request->input('status', 'active');

        $summary = ChargeAlertQueue::summarize(ChargeAlertQueue::fuelAlerts(), 'fuel');

        $items = $status === 'active'
            ? $this->activeQueue($request)
            : $this->terminalRecords($request, $status);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $alerts = new LengthAwarePaginator(
            $items->forPage($page, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->boolean('fragment')) {
            return response()->json([
                'success' => true,
                'queue_html' => view('admin.reports.fuel_charge_workspace.partials._queue', compact('alerts'))->render(),
                'summary_html' => view('admin.reports.fuel_charge_workspace.partials._summary', compact('summary'))->render(),
            ]);
        }

        // Modal support data — same sources the old dashboard modals used.
        $users             = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $paymentSetting    = ConfigurationHelper::getSettings('Payment Settings');
        $resolutionPresets = ResolutionNotePreset::orderBy('label')->get(['id', 'label']);
        $fuelNotePresets   = FuelNotePreset::orderBy('label')->get(['id', 'label']);

        return view('admin.reports.fuel_charge_workspace.index', compact(
            'alerts', 'summary', 'status', 'users', 'paymentSetting', 'resolutionPresets', 'fuelNotePresets'
        ));
    }

    /**
     * The canonical outstanding queue, filtered in memory. Rows keep the
     * exact shape ChargeAlertQueue builds (the same shape the dashboard
     * consumed), so the view renders one contract for both sources.
     */
    private function activeQueue(Request $request): Collection
    {
        $items = ChargeAlertQueue::fuelAlerts();

        if ($request->filled('search_name')) {
            $needle = mb_strtolower($request->search_name);
            $items = $items->filter(
                fn ($a) => str_contains(mb_strtolower($a['customerName'] ?? ''), $needle)
            );
        }

        if ($request->filled('search_order')) {
            $needle = mb_strtolower($request->search_order);
            $items = $items->filter(
                fn ($a) => str_contains(mb_strtolower((string) ($a['order_number'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($a['orderId'] ?? '')), $needle)
            );
        }

        return $items->values();
    }

    /**
     * Historical terminal records for the status filter — same dual-source
     * union the existing Fuel report tab uses, normalized into the queue
     * row shape so the partial renders identically. Not part of the
     * dashboard reconciliation (terminal rows have left the queue).
     */
    private function terminalRecords(Request $request, string $status): Collection
    {
        $opQuery = OrderProduct::with(['order.customer.cards', 'equipment', 'fuelChargeLogs'])
            ->whereNotNull('fuel_total_charge')
            ->where('fuel_total_charge', '>', 0)
            ->where('fuel_charge_status', $status)
            ->whereHas('order');

        $crmQuery = CustomerAccount::with(['customer.cards', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Fuel Charge')
            ->whereNull('order_product_id')
            ->where('fuel_alert_status', $status);

        if ($request->filled('search_name')) {
            $needle = $request->search_name;
            $opQuery->whereHas('order.customer', fn ($q) => $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$needle}%"]));
            $crmQuery->whereHas('customer', fn ($q) => $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$needle}%"]));
        }

        if ($request->filled('search_order')) {
            $needle = $request->search_order;
            $opQuery->whereHas('order', fn ($q) => $q->where('order_number', 'like', "%{$needle}%")->orWhere('unique_id', 'like', "%{$needle}%"));
            $crmQuery->whereHas('order', fn ($q) => $q->where('order_number', 'like', "%{$needle}%")->orWhere('unique_id', 'like', "%{$needle}%"));
        }

        $opRows = $opQuery->latest('id')->get()->map(function ($op) use ($status) {
            $base = (float) ($op->fuel_total_charge ?? 0);
            $current = max(0, $base + $op->fuelChargeLogs->sum('change_amount'));

            return [
                'customerName' => $op->order?->customer?->full_name ?? '—',
                'customer' => ['id' => $op->order?->customer?->id, 'cards' => collect()],
                'orderId' => $op->order?->unique_id,
                'order_number' => $op->order?->order_number ?? '—',
                'orderLink' => $op->order ? route('admin.order-management.orders.edit', $op->order->unique_id) : null,
                'amountOwed' => '$' . number_format($current, 2),
                'date' => optional($op->order?->created_at)->toDateString(),
                '_sort_ts' => $op->created_at?->timestamp ?? 0,
                'type' => 'fuel',
                'notes' => collect(),
                'equipment' => ['name' => $op->equipment?->equipment_name],
                'order_product' => ['id' => $op->id, 'unique_id' => $op->unique_id, 'current_fuel_charge' => $current],
                'terminal_status' => $status,
            ];
        });

        $crmRows = $crmQuery->latest()->get()->map(function ($account) use ($status) {
            $base = (float) ($account->amount ?? 0);
            $rate = (float) ($account->sales_tax ?? 0);
            $total = ($account->sales_tax_type === 'add' && $rate > 0) ? $base + $base * $rate : $base;

            return [
                'source' => 'crm',
                'customerName' => $account->customer?->full_name ?? '—',
                'customer' => ['id' => $account->customer_id, 'cards' => collect()],
                'orderId' => $account->order?->unique_id,
                'order_number' => $account->order?->order_number,
                'orderLink' => $account->order
                    ? route('admin.order-management.orders.edit', $account->order->unique_id)
                    : ($account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null),
                'crmLink' => $account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null,
                'amountOwed' => '$' . number_format($total, 2),
                'date' => optional($account->date)->toDateString(),
                '_sort_ts' => ($account->date ?? $account->created_at)?->timestamp ?? 0,
                'type' => 'fuel',
                'notes' => collect(),
                'equipment' => null,
                'order_product' => null,
                'customer_account_id' => $account->unique_id,
                'terminal_status' => $status,
            ];
        });

        return $opRows->concat($crmRows)->sortByDesc('_sort_ts')->values();
    }
}

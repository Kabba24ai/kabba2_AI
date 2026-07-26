<?php

namespace App\Http\Controllers\Admin\Tasks\Billing\DamageCharges;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Dashboard\ResolutionNotePreset;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderProduct;
use App\Services\Alerts\ChargeAlertQueue;
use App\Services\Billing\BillingOperationsSummary;
use App\Services\Billing\DamageChargeSourceResolver;
use App\Services\Orders\BillingChargeRefundService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Task Manager → Billing Operations → Damage Charge Resolution.
 *
 * A sibling activity workspace to Fuel Charge Resolution — SAME shell, same
 * shared queue/summary/action components, same canonical Billing Engine
 * endpoints — but a SEPARATE queue (never mixed with fuel). The active queue
 * comes from BillingOperationsSummary::damageActiveAlerts() — the SINGLE
 * canonical dataset also consumed by the Billing Operations Overview, so the
 * workspace count and the Overview count can never drift.
 *
 * This is a billing-resolution surface only: the damage EVIDENCE and
 * investigation stay in the originating Customer Checklist / Service Ticket.
 * Each row carries a canonical "Source" link back to that record
 * (DamageChargeSourceResolver); rows with no provable source are shown
 * honestly without a source link. Every mutating action posts to the
 * pre-existing, charge-type-agnostic dashboard/Billing Engine endpoints.
 */
class IndexController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private BillingOperationsSummary $summaryService)
    {
    }

    public function __invoke(Request $request)
    {
        $status = $request->input('status', 'active');

        // Summary is always the FULL active damage set — the needs_pricing
        // filter narrows only the displayed queue, never the summary metrics.
        $summary = ChargeAlertQueue::summarize($this->summaryService->damageActiveAlerts(), 'damage');

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
                'queue_html' => view('admin.tasks.billing.partials._queue', ['alerts' => $alerts, 'chargeType' => 'damage'])->render(),
                'summary_html' => view('admin.tasks.billing.partials._summary', compact('summary'))->render(),
            ]);
        }

        // Modal support data — same shared billing action modals as fuel.
        // No damage-specific note presets exist yet, so the type-note preset
        // list is intentionally empty (Resolve/Uncollectible still use the
        // shared ResolutionNotePreset set). Documented limitation.
        $users             = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $paymentSetting    = ConfigurationHelper::getSettings('Payment Settings');
        $resolutionPresets = ResolutionNotePreset::ordered()->get(['id', 'label', 'sort_order']);
        $fuelNotePresets   = collect();

        return view('admin.tasks.billing.damage_charges.index', compact(
            'alerts', 'summary', 'status', 'users', 'paymentSetting', 'resolutionPresets', 'fuelNotePresets'
        ));
    }

    /**
     * The displayed active DAMAGE queue: the canonical active set (from the
     * shared BillingOperationsSummary — dashboard damage alerts PLUS
     * qualifying Service Ticket damage charges, the SAME dataset the Overview
     * counts), narrowed by the workspace filters, then enriched with source
     * links. The needs_pricing filter narrows only what is shown here.
     */
    private function activeQueue(Request $request): Collection
    {
        $items = $this->summaryService->damageActiveAlerts();

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

        // Needs Pricing filter — deep-linked from the Overview card. Shows
        // only visible active damage records with no collectible amount
        // ($0 / Pending). Affects the displayed queue ONLY.
        if ($request->boolean('needs_pricing')) {
            $items = $items->filter(fn ($a) => BillingOperationsSummary::isUnpriced($a));
        }

        return DamageChargeSourceResolver::enrich($items->values());
    }

    /**
     * Historical terminal damage records for the status filter — the dual
     * OrderProduct + CustomerAccount union, normalized into the queue row
     * shape and enriched with the BillingCharge bridge + source links.
     */
    private function terminalRecords(Request $request, string $status): Collection
    {
        $opQuery = OrderProduct::with(['order.customer.cards', 'equipment', 'damageChargeLogs'])
            ->whereNotNull('damage_charge')
            ->where('damage_charge', '>', 0)
            ->where('damage_status', $status)
            ->whereHas('order');

        $crmQuery = CustomerAccount::with(['customer.cards', 'order'])
            ->where('type', 'charge')
            ->where('reason', 'Damages')
            ->whereNull('order_product_id')
            ->where('damage_alert_status', $status);

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

        $opRecords  = $opQuery->latest('id')->get();
        $crmRecords = $crmQuery->latest()->get();

        $opBridges = BillingCharge::whereIn('order_product_id', $opRecords->pluck('id'))
            ->where('billing_charge_type', 'damage')
            ->get()
            ->keyBy('order_product_id');
        $crmBridges = BillingCharge::whereIn('customer_account_id', $crmRecords->pluck('id'))
            ->where('billing_charge_type', 'damage')
            ->get()
            ->keyBy('customer_account_id');

        $refundRemaining = function (?BillingCharge $bridge): float {
            if (!$bridge || !$bridge->isPaid()) {
                return 0.0;
            }

            return (float) BillingChargeRefundService::remainingRefundable($bridge)['total'];
        };

        $opRows = $opRecords->map(function ($op) use ($status, $opBridges, $refundRemaining) {
            $base = (float) ($op->damage_charge ?? 0);
            $current = max(0, $base + $op->damageChargeLogs->sum('change_amount'));
            $bridge = $opBridges->get($op->id);

            return [
                'customerName' => $op->order?->customer?->full_name ?? '—',
                'customer' => ['id' => $op->order?->customer?->id, 'cards' => collect()],
                'orderId' => $op->order?->unique_id,
                'order_number' => $op->order?->order_number ?? '—',
                'order_db_id' => $op->order?->id,
                'orderLink' => $op->order ? route('admin.order-management.orders.edit', $op->order->unique_id) : null,
                'amountOwed' => '$' . number_format($current, 2),
                'date' => optional($op->order?->created_at)->toDateString(),
                '_sort_ts' => $op->created_at?->timestamp ?? 0,
                'type' => 'damage',
                'notes' => collect(),
                'equipment' => ['name' => $op->equipment?->equipment_name],
                'order_product' => ['id' => $op->id, 'unique_id' => $op->unique_id, 'current_damage_charge' => $current],
                'billing_charge_unique_id' => $bridge?->unique_id,
                'refund_remaining' => $refundRemaining($bridge),
                'terminal_status' => $status,
            ];
        });

        $crmRows = $crmRecords->map(function ($account) use ($status, $crmBridges, $refundRemaining) {
            $base = (float) ($account->amount ?? 0);
            $rate = (float) ($account->sales_tax ?? 0);
            $total = ($account->sales_tax_type === 'add' && $rate > 0) ? $base + $base * $rate : $base;
            $bridge = $crmBridges->get($account->id);

            return [
                'source' => 'crm',
                'customerName' => $account->customer?->full_name ?? '—',
                'customer' => ['id' => $account->customer_id, 'cards' => collect()],
                'orderId' => $account->order?->unique_id,
                'order_number' => $account->order?->order_number,
                'order_db_id' => $account->order?->id,
                'orderLink' => $account->order
                    ? route('admin.order-management.orders.edit', $account->order->unique_id)
                    : ($account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null),
                'crmLink' => $account->customer ? route('admin.crm.customers.view', $account->customer->unique_id) : null,
                'amountOwed' => '$' . number_format($total, 2),
                'date' => optional($account->date)->toDateString(),
                '_sort_ts' => ($account->date ?? $account->created_at)?->timestamp ?? 0,
                'type' => 'damage',
                'notes' => collect(),
                'equipment' => null,
                'order_product' => null,
                'customer_account_id' => $account->unique_id,
                'billing_charge_unique_id' => $bridge?->unique_id,
                'refund_remaining' => $refundRemaining($bridge),
                'terminal_status' => $status,
            ];
        });

        return DamageChargeSourceResolver::enrich($opRows->concat($crmRows)->sortByDesc('_sort_ts')->values());
    }
}

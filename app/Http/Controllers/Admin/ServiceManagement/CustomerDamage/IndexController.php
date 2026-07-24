<?php

namespace App\Http\Controllers\Admin\ServiceManagement\CustomerDamage;

use App\Http\Controllers\Controller;
use App\Models\Dashboard\ResolutionNotePreset;
use App\Models\Service\CustomerDamageStaging;
use Illuminate\Http\Request;

/**
 * Customer Damage Staging — the review queue. Read-only: every action
 * (manual creation, disposition) posts to its own endpoint, and all
 * downstream work runs through the canonical Billing / Service Ticket
 * paths via CustomerDamageStagingService.
 */
class IndexController extends Controller
{
    private const PER_PAGE = 15;

    public function __invoke(Request $request)
    {
        $filter = $request->input('filter', 'active');

        $query = CustomerDamageStaging::with([
            'order', 'customer', 'equipment', 'reportedBy', 'disposedBy',
            'serviceTicket', 'billingCharge',
        ])->latest('reported_at');

        match ($filter) {
            'new'            => $query->where('status', CustomerDamageStaging::STATUS_NEW),
            'in_review'      => $query->where('status', CustomerDamageStaging::STATUS_IN_REVIEW),
            'ticket_created' => $query->whereNotNull('service_ticket_id'),
            'charge_created' => $query->whereNotNull('billing_charge_id'),
            'no_action'      => $query->where('disposition', CustomerDamageStaging::DISPOSITION_NO_ACTION),
            'all'            => $query,
            default          => $query->active(), // 'active' — the working queue
        };

        if ($request->filled('search')) {
            $needle = $request->search;
            $query->where(function ($q) use ($needle) {
                $q->whereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$needle}%"))
                    ->orWhereHas('customer', fn ($c) => $c->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$needle}%"]))
                    ->orWhereHas('equipment', fn ($e) => $e->where('equipment_name', 'like', "%{$needle}%"));
            });
        }

        $records = $query->paginate(self::PER_PAGE)->withQueryString();

        $summary = [
            'new'            => CustomerDamageStaging::where('status', CustomerDamageStaging::STATUS_NEW)->count(),
            'in_review'      => CustomerDamageStaging::where('status', CustomerDamageStaging::STATUS_IN_REVIEW)->count(),
            'ticket_linked'  => CustomerDamageStaging::whereNotNull('service_ticket_id')->count(),
            'resolved_today' => CustomerDamageStaging::where('status', CustomerDamageStaging::STATUS_DISPOSED)
                ->whereDate('disposed_at', today())
                ->count(),
        ];

        return view('admin.service_management.customer_damage.index', [
            'records'           => $records,
            'summary'           => $summary,
            'filter'            => $filter,
            'resolutionPresets' => ResolutionNotePreset::ordered()->get(['id', 'label', 'sort_order']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\Warranty\Cases;

use App\Enums\Warranty\WarrantyPath;
use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // KPI cards: live counts per queue, unaffected by filters
        $queueCounts = WarrantyCase::selectRaw('queue, count(*) as total')
            ->groupBy('queue')
            ->pluck('total', 'queue');

        $query = WarrantyCase::with(['customer', 'equipment', 'serviceTicket']);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('queue')) {
            $query->where('queue', $request->input('queue'));
        } elseif (!$request->boolean('include_closed')) {
            $query->where('queue', '!=', WarrantyQueue::Closed->value);
        }

        if ($request->filled('path')) {
            $query->where('path', $request->input('path'));
        }

        if ($request->filled('manufacturer')) {
            $query->where('manufacturer', $request->input('manufacturer'));
        }

        $cases = $query->latest('id')->paginate(24)->withQueryString();

        $manufacturers = WarrantyCase::whereNull('deleted_at')
            ->distinct()->orderBy('manufacturer')->pluck('manufacturer');

        return view('admin.warranty.index', [
            'cases'         => $cases,
            'queueCounts'   => $queueCounts,
            'queues'        => WarrantyQueue::cases(),
            'paths'         => WarrantyPath::cases(),
            'manufacturers' => $manufacturers,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Enums\FieldService\FieldMissionStatus;
use App\Enums\Service\ServicePriority;
use App\Http\Controllers\Controller;
use App\Models\FieldService\FieldServiceTicket;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = FieldServiceTicket::with(['customer', 'equipment', 'order', 'technician']);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('job_site_address', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhereHas('equipment', fn ($eq) => $eq
                        ->where('equipment_name', 'like', "%{$search}%")
                        ->orWhere('equipment_id', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('mission_status')) {
            $query->where('mission_status', $request->input('mission_status'));
        } elseif (!$request->boolean('include_closed')) {
            $query->whereNotIn('mission_status', [
                FieldMissionStatus::Completed->value,
                FieldMissionStatus::Cancelled->value,
            ]);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('technician')) {
            $query->where('technician_id', $request->input('technician'));
        }

        $tickets = $query
            ->orderByRaw(ServicePriority::sqlOrder())
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.field_service.index', [
            'tickets'    => $tickets,
            'statuses'   => FieldMissionStatus::cases(),
            'priorities' => ServicePriority::cases(),
        ]);
    }
}

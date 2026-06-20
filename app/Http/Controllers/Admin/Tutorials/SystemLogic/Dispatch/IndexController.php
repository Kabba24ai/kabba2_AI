<?php

namespace App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch;

use App\Enums\Tutorials\SystemLogicStatus;
use App\Enums\Tutorials\SystemLogicVisibility;
use App\Http\Controllers\Controller;
use App\Models\Tutorials\SystemLogicDocument;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = SystemLogicDocument::with('creator', 'updater')
            ->forModule('dispatch')
            ->orderBy('sort_order')
            ->orderBy('section_key')
            ->orderBy('created_at');

        if ($request->filled('section')) {
            $query->where('section_key', $request->section);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('summary', 'like', "%{$term}%")
                  ->orWhere('logic_body', 'like', "%{$term}%");
            });
        }

        $documents = $query->get();

        $sections   = SystemLogicDocument::forModule('dispatch')->distinct()->orderBy('section_key')->pluck('section_key');
        $statuses   = SystemLogicStatus::cases();
        $visibilities = SystemLogicVisibility::cases();

        $sectionLabels = self::sectionLabels();

        return view('admin.tutorials.system_logic.dispatch.index', compact(
            'documents', 'sections', 'statuses', 'visibilities', 'sectionLabels'
        ));
    }

    public static function sectionLabels(): array
    {
        return [
            'driver_assignment_logic'     => 'Driver Assignment Logic',
            'truck_assignment_logic'      => 'Truck Assignment Logic',
            'trailer_assignment_logic'    => 'Trailer Assignment Logic',
            'equipment_transport_logic'   => 'Equipment Transport Logic',
            'routing_logic'               => 'Routing Logic',
            'early_delivery_logic'        => 'Early Delivery Logic',
            'schedule_conflict_logic'     => 'Schedule Conflict Logic',
            'ai_dispatch_logic'           => 'AI Dispatch Logic',
            'manual_override_logic'       => 'Manual Override Logic',
            'customer_communication_logic'=> 'Customer Communication Logic',
            'manager_approval_logic'      => 'Manager Approval Logic',
            'safety_compliance_logic'     => 'Safety / Compliance Logic',
        ];
    }
}

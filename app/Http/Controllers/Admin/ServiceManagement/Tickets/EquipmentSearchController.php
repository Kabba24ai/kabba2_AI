<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Standard Equipment Ticket — category-scoped equipment search.
 *
 * GET service-management/tickets/equipment-search?category_id=&search=
 *
 * The Standard intake path selects one known fleet unit directly (no order,
 * no customer). This is the SAME server-side, capped search architecture the
 * Queue Line board's equipment-candidates endpoint uses — the full fleet is
 * never loaded into one dropdown. Matching is deliberately narrow: Equipment
 * Name and Equipment ID only (never product / category / serial), and the
 * result set is hard-scoped to the chosen category so a unit outside it can
 * never appear. The server re-validates category membership at store time
 * (SaveTicketRequest) — this endpoint is a UX aid, not the trust boundary.
 */
class EquipmentSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $categoryId = (int) $request->query('category_id');
        $term       = trim((string) $request->query('search', ''));

        // Category is mandatory — Equipment is only offered once a category is
        // chosen, and only from within it.
        if ($categoryId <= 0) {
            return response()->json(['data' => []]);
        }

        $units = Equipment::query()
            ->where('product_category_id', $categoryId)
            ->when($term !== '', function ($q) use ($term) {
                // Name + Equipment ID only — anything else is noise.
                $q->where(function ($q) use ($term) {
                    $q->where('equipment_name', 'like', "%{$term}%")
                        ->orWhere('equipment_id', 'like', "%{$term}%");
                });
            })
            ->orderByRaw('equipment_id = ? DESC', [$term]) // exact id (barcode) first
            ->orderBy('equipment_name')
            ->limit(25)
            ->get(['id', 'equipment_name', 'equipment_id', 'assigned_product_id', 'product_category_id', 'service_symptom_profile_id']);

        return response()->json([
            'data' => $units->map(fn (Equipment $unit) => [
                'id'         => $unit->id,
                'display_id' => $unit->equipment_id,
                'name'       => $unit->equipment_name,
                'label'      => $unit->equipment_name . ($unit->equipment_id ? ' (' . $unit->equipment_id . ')' : ''),
                // Keys the shared complaint engine resolves symptoms from — the
                // Standard unit feeds the exact same resolution as an order unit.
                'product_id'          => $unit->assigned_product_id,
                'product_category_id' => $unit->product_category_id,
                'symptom_profile_id'  => $unit->service_symptom_profile_id,
            ])->values(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin\V1\QueueLine;

use App\Http\Controllers\Api\Admin\V1\QueueLine\Concerns\RespondsWithQueueLineEnvelope;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\QueueLine\QueueLineEligibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Queue Line Equipment Candidates
 *
 * GET queue-line/{order_product_unique_id}/equipment-candidates?search=
 *
 * Candidate units for assignment/switch — the SAME ordering rules as the
 * web board picker: exact display-id match first (barcode scan), then
 * direct ordered-product matches, then other searchable active equipment.
 * The dormant substitution scoring engine is NOT consulted (approved).
 *
 * Physically rented units are excluded (hard canonical integrity rule);
 * maintenance/damaged units remain listed with their status (approved —
 * visible and stageable). Future scheduling conflicts NEVER remove a
 * candidate. `requires_reason` tells the app whether the canonical service
 * will demand a reason (non-direct match) — the rule itself stays server-side.
 *
 */
class QueueLineEquipmentCandidatesController extends Controller
{
    use RespondsWithQueueLineEnvelope;

    /**
     * Queue Line Equipment Candidates
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(Request $request, string $orderProductUniqueId): JsonResponse
    {
        $item = $this->findQueueItem($orderProductUniqueId);

        if (! $item) {
            return $this->itemNotFound();
        }

        $term = trim((string) $request->query('search', ''));

        $candidates = Equipment::query()
            ->where('current_status', '!=', 'rented') // hard integrity rule — in the field
            ->when($item->softAssignment?->equipment_id, fn ($q, $current) => $q->where('id', '!=', $current))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($q) use ($term) {
                    $q->where('equipment_id', 'like', "%{$term}%")
                        ->orWhere('equipment_name', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%");
                });
            })
            ->with(['assignedProduct:id,product_name', 'store:id,unique_id,store_name'])
            ->orderByRaw('equipment_id = ? DESC', [$term])                    // barcode scan → exact id first
            ->orderByRaw('assigned_product_id = ? DESC', [$item->product_id]) // direct matches next
            ->orderBy('equipment_name')
            ->limit(25)
            ->get();

        $orderedProductId = (int) $item->product_id;

        $data = $candidates->map(function (Equipment $unit) use ($orderedProductId, $term) {
            $match = $unit->assigned_product_id === null
                ? 'assignment_product_unknown'
                : ((int) $unit->assigned_product_id === $orderedProductId ? 'direct' : 'alternate');

            return [
                'unique_id' => $unit->unique_id,
                'display_id' => $unit->equipment_id,
                'name' => $unit->equipment_name,
                'brand' => $unit->brand,
                'assigned_product_name' => $unit->assignedProduct?->product_name,
                'match' => $match,
                'requires_reason' => $match !== 'direct', // canonical rule, surfaced for the UI
                'status' => $unit->current_status?->value,
                'status_label' => $unit->status_label,
                'store' => $unit->store ? [
                    'unique_id' => $unit->store->unique_id,
                    'name' => $unit->store->store_name,
                ] : null,
                'exact_scan_match' => $term !== '' && $unit->equipment_id === $term,
            ];
        })->values()->all();

        return $this->ok(
            ['candidates' => $data],
            [
                'search' => $term ?: null,
                'ordered_product' => [
                    'unique_id' => $item->product?->unique_id,
                    'name' => $item->product_name,
                ],
                'current_equipment' => $item->softAssignment?->equipment ? [
                    'unique_id' => $item->softAssignment->equipment->unique_id,
                    'display_id' => $item->softAssignment->equipment->equipment_id,
                    'name' => $item->softAssignment->equipment->equipment_name,
                ] : null,
            ],
        );
    }
}

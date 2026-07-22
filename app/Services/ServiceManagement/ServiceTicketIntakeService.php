<?php

namespace App\Services\ServiceManagement;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Enums\Service\ServiceTicketEventType;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * ST-1 — THE canonical, idempotent Service Ticket creation path. Every
 * caller (the web intake form, Warranty case intake, and future modules
 * such as Customer Damage) creates tickets through this one service instead
 * of hand-rolling ServiceTicket::create + the same follow-on steps. This
 * both removes the duplicated intake logic and gives creation a real
 * idempotency guard — a double-submit or a repeated programmatic call
 * returns the FIRST ticket rather than minting a second.
 *
 * Encapsulated, in one transaction:
 *   1. canonical intake defaults (only where the caller didn't specify)
 *   2. the ServiceTicket row (carrying the optional idempotency key)
 *   3. the EquipmentOverride audit event, when an override is present
 *   4. structured complaint snapshots (name + category label frozen)
 *   5. personnel + team-leader sync
 *   6. transitionTo() to stamp completion timestamps for a direct-to-closed
 *      create
 *
 * HTTP-only work (evidence file uploads) stays in the controller and runs
 * after this returns, guarded by $ticket->wasRecentlyCreated so an
 * idempotent short-circuit never re-uploads.
 */
class ServiceTicketIntakeService
{
    /**
     * @param  array        $attributes         Ticket column values (the caller merges
     *                                           its own order/override/context fields).
     * @param  int[]        $complaintSymptomIds Selected symptom-library ids to snapshot.
     * @param  int[]        $personnelIds        Assigned user ids.
     * @param  int|null     $teamLeaderId        Team leader (must be among $personnelIds).
     * @param  string|null  $idempotencyKey      When set, a repeat returns the first ticket.
     */
    public static function create(
        array $attributes,
        array $complaintSymptomIds = [],
        array $personnelIds = [],
        ?int $teamLeaderId = null,
        ?string $idempotencyKey = null,
    ): ServiceTicket {
        // Fast path: a known key already has a ticket — return it untouched
        // (wasRecentlyCreated stays false, so the caller skips media/etc.).
        if ($idempotencyKey !== null) {
            $existing = ServiceTicket::where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $actorId = auth()->id();

        // Canonical intake defaults — applied ONLY where the caller left the
        // field unset, so an explicit choice (e.g. Warranty's OemWarrantyRepair)
        // always wins. These are the same defaults the web intake form used.
        $attributes = array_merge([
            'service_type'             => ServiceType::CustomerDamageRepair->value,
            'service_location'         => ServiceLocation::InShop->value,
            'priority'                 => ServicePriority::Normal->value,
            'repair_status'            => RepairStatus::Open->value,
            'financial_responsibility' => FinancialResponsibility::Pending->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'opened_at'                => now()->format('Y-m-d'),
            'created_by'               => $actorId,
            'updated_by'               => $actorId,
        ], array_filter($attributes, fn ($v) => $v !== null));

        $attributes['idempotency_key'] = $idempotencyKey;

        try {
            return DB::transaction(function () use ($attributes, $complaintSymptomIds, $personnelIds, $teamLeaderId) {
                $ticket = ServiceTicket::create($attributes);

                // Fully traceable override: original unit → corrected unit,
                // who, when, why (identical to the prior inline behavior).
                if ($ticket->equipment_override) {
                    ServiceTicketEvent::record(
                        $ticket->id,
                        ServiceTicketEventType::EquipmentOverride,
                        $ticket->orderEquipment?->equipment_name . ($ticket->orderEquipment?->equipment_id ? ' (' . $ticket->orderEquipment->equipment_id . ')' : ''),
                        $ticket->equipment?->equipment_name . ($ticket->equipment?->equipment_id ? ' (' . $ticket->equipment->equipment_id . ')' : ''),
                        $ticket->equipment_override_reason,
                    );
                }

                // Structured complaints — name/category snapshots so the
                // ticket keeps what was reported even if the library changes.
                if ($complaintSymptomIds !== []) {
                    $symptoms = ServiceSymptom::with('category')->whereIn('id', $complaintSymptomIds)->get();
                    foreach ($symptoms as $symptom) {
                        $ticket->complaints()->create([
                            'service_symptom_id' => $symptom->id,
                            'name'               => $symptom->name,
                            'system_group'       => $symptom->category?->name,
                        ]);
                    }
                }

                $ticket->syncPersonnel($personnelIds, $teamLeaderId);

                // Stamp completed/closed timestamps if created directly in a
                // terminal state (edge case; keeps data consistent).
                $ticket->transitionTo($ticket->repair_status, $ticket->blocked_reason, $ticket->expected_action_date);

                return $ticket;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Concurrent double-submit lost the race on the unique key — the
            // winner's ticket is the canonical one.
            if ($idempotencyKey !== null) {
                $winner = ServiceTicket::where('idempotency_key', $idempotencyKey)->first();
                if ($winner !== null) {
                    return $winner;
                }
            }

            throw $e;
        }
    }
}

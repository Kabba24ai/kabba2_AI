<?php

namespace App\Services\Service;

use App\Enums\Service\ServiceType;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderProduct;
use App\Models\Service\CustomerDamageStaging;
use App\Models\Service\ServiceTicket;
use App\Services\ChargeService;
use App\Services\ServiceManagement\ServiceTicketIntakeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Customer Damage Staging — ingestion + disposition. This service owns NO
 * downstream business logic: charges go through the canonical
 * ChargeService::createManualCharge (the Billing Engine path every manual
 * damage charge uses), tickets through ServiceTicket::create with the same
 * defaults the ticket intake and Warranty intake use. Staging only decides
 * WHEN those canonical paths run and links the results.
 */
class CustomerDamageStagingService
{
    /**
     * Ingest one return-checklist damage observation. Idempotent by
     * construction: the source key mirrors the mobile damage BillingCharge
     * idempotency key (mobile_checklist:{opId}:damage:{cycleKey} — see
     * BillingChargeRequest::mobileReturnDamage), backed by a DB unique
     * index, so checklist retries and duplicate API submissions can never
     * create a second staging row.
     *
     * Never throws to the caller — a staging failure must not break the
     * return-checklist save (same discipline as the Billing Engine bridge).
     */
    public static function ingestFromReturnChecklist(
        OrderProduct $orderProduct,
        string $cycleKey,
        ?int $reportedByUserId,
    ): ?CustomerDamageStaging {
        try {
            $sourceKey = "mobile_checklist:{$orderProduct->id}:damage:{$cycleKey}";

            $existing = CustomerDamageStaging::where('source_key', $sourceKey)->first();
            if ($existing) {
                return $existing; // retry / duplicate submission — no-op
            }

            $order = $orderProduct->order;
            if (!$order) {
                Log::warning("CustomerDamageStaging: OrderProduct {$orderProduct->id} has no order — skipped.");

                return null; // Customer Damage is order-based
            }

            // Defensive mirror of the caller's damage gate: a return with
            // no damaged answer never creates a staging record.
            $hasDamage = $orderProduct->checklistQuestions()
                ->whereHas('answers', fn ($q) => $q
                    ->where('is_return_answer', true)
                    ->whereHas('answer', fn ($a) => $a->where('is_damaged', true)))
                ->exists();

            if (!$hasDamage) {
                return null;
            }

            // The checklist damage BillingCharge (often $0 "Pending") was
            // just created by the same request under this exact key — link
            // it, NEVER create another.
            $billingCharge = BillingCharge::where('idempotency_key', $sourceKey)->first();

            return CustomerDamageStaging::create([
                'source_type'       => 'checklist',
                'source_key'        => $sourceKey,
                'order_id'          => $order->id,
                'customer_id'       => $order->customer_id,
                'order_product_id'  => $orderProduct->id,
                'equipment_id'      => $orderProduct->equipment_id,
                'store_id'          => $orderProduct->pickup_store_id ?? $orderProduct->delivery_store_id,
                'observation'       => self::buildChecklistObservation($orderProduct),
                'reported_by'       => $reportedByUserId,
                'reported_at'       => now(),
                'status'            => CustomerDamageStaging::STATUS_NEW,
                'billing_charge_id' => $billingCharge?->id,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Concurrent duplicate — the unique index did its job.
            return CustomerDamageStaging::where('source_key', "mobile_checklist:{$orderProduct->id}:damage:{$cycleKey}")->first();
        } catch (\Throwable $e) {
            report($e);
            Log::error("CustomerDamageStaging ingest failed | order_product_id={$orderProduct->id} | " . $e->getMessage());

            return null;
        }
    }

    /**
     * Snapshot the field observation: each damaged return answer (question,
     * chosen answer, driver-entered amount) plus the return note. Evidence
     * text — stays stable even if checklist rows are later recreated.
     */
    private static function buildChecklistObservation(OrderProduct $orderProduct): string
    {
        $lines = [];

        foreach ($orderProduct->checklistQuestions()->with('answers.answer')->get() as $question) {
            foreach ($question->answers as $answerRow) {
                if (!$answerRow->is_return_answer || !($answerRow->answer?->is_damaged)) {
                    continue;
                }

                $label = $answerRow->return_answer
                    ?: ($answerRow->answer?->answer_return_text ?? 'Damaged');
                $amount = (float) ($answerRow->user_return_amount ?? 0);

                $lines[] = trim(
                    ($question->question_name ?? 'Checklist item') . ': ' . $label
                    . ($amount > 0 ? ' — driver amount $' . number_format($amount, 2) : '')
                );
            }
        }

        if (filled($orderProduct->pickup_notes)) {
            $lines[] = 'Return note: ' . $orderProduct->pickup_notes;
        }

        return $lines === []
            ? 'Customer damage reported on return checklist.'
            : implode("\n", $lines);
    }

    /** Optional light state: reviewer parks the record as In Review. */
    public static function markInReview(CustomerDamageStaging $staging, int $userId, ?string $note): CustomerDamageStaging
    {
        return DB::transaction(function () use ($staging, $userId, $note) {
            $locked = CustomerDamageStaging::lockForUpdate()->findOrFail($staging->id);
            self::guardNotDisposed($locked);

            $locked->status = CustomerDamageStaging::STATUS_IN_REVIEW;
            if (filled($note)) {
                $locked->disposition_note = self::appendNote($locked->disposition_note, $note, $userId);
            }
            $locked->save();

            return $locked;
        });
    }

    /** No Action Required — closes the record; note is mandatory. */
    public static function disposeNoAction(CustomerDamageStaging $staging, int $userId, string $note): CustomerDamageStaging
    {
        return DB::transaction(function () use ($staging, $userId, $note) {
            $locked = CustomerDamageStaging::lockForUpdate()->findOrFail($staging->id);
            self::guardNotDisposed($locked);

            if ($locked->service_ticket_id || $locked->billing_charge_id) {
                throw new \RuntimeException('This record already has a linked ticket or charge — No Action is not applicable.');
            }

            $locked->fill([
                'status'           => CustomerDamageStaging::STATUS_DISPOSED,
                'disposition'      => CustomerDamageStaging::DISPOSITION_NO_ACTION,
                'disposition_note' => self::appendNote($locked->disposition_note, $note, $userId),
                'disposed_by'      => $userId,
                'disposed_at'      => now(),
            ])->save();

            return $locked;
        });
    }

    /**
     * Charge Customer. Checklist rows usually ALREADY hold the (often $0)
     * damage BillingCharge the mobile bridge created — that link is kept
     * and no second charge is ever created (approved rule); the reviewer
     * prices/collects it through the canonical Billing Engine surfaces.
     * Rows without a linked charge (manual source) create one through
     * ChargeService::createManualCharge — the same path every manual
     * damage charge takes, including its idempotent Billing Engine bridge.
     */
    public static function disposeChargeCustomer(
        CustomerDamageStaging $staging,
        int $userId,
        ?float $amount,
        ?string $salesTaxType,
        ?string $note,
    ): CustomerDamageStaging {
        // Canonical charge creation happens OUTSIDE the staging row lock
        // (ChargeService manages its own transaction), so a request-level
        // lock serializes concurrent disposition attempts — a double-click
        // can never create two charges. The link re-check under row lock
        // below is the second line of defense.
        $requestLock = \Illuminate\Support\Facades\Cache::lock("customer-damage-charge:{$staging->id}", 20);
        if (!$requestLock->get()) {
            throw new \RuntimeException('This disposition is already being processed — refresh to see the result.');
        }

        try {
            return self::runChargeDisposition($staging, $userId, $amount, $salesTaxType, $note);
        } finally {
            $requestLock->release();
        }
    }

    private static function runChargeDisposition(
        CustomerDamageStaging $staging,
        int $userId,
        ?float $amount,
        ?string $salesTaxType,
        ?string $note,
    ): CustomerDamageStaging {
        $locked = $staging->fresh();

        if (!$locked->billing_charge_id) {
            if ($amount === null || $amount <= 0) {
                throw new \RuntimeException('Enter the charge amount.');
            }
            if (!$locked->customer_id) {
                throw new \RuntimeException('This record has no customer to charge.');
            }

            $account = ChargeService::createManualCharge(
                customerId: (int) $locked->customer_id,
                type: 'damage',
                amount: $amount,
                salesTaxType: $salesTaxType,
                notes: $note ?: ('Customer damage ' . $locked->unique_id),
                responsibleUserId: $userId,
                orderId: $locked->order_id,
                sourceContext: 'customer_damage',
            );

            $bridge = BillingCharge::where('idempotency_key', "manual_damage_charge:{$account->id}")->first();

            DB::transaction(function () use ($locked, $bridge) {
                $row = CustomerDamageStaging::lockForUpdate()->findOrFail($locked->id);
                if (!$row->billing_charge_id) {
                    $row->billing_charge_id = $bridge?->id;
                    $row->save();
                }
            });
        }

        return DB::transaction(function () use ($staging, $userId, $note) {
            $row = CustomerDamageStaging::lockForUpdate()->findOrFail($staging->id);

            $row->fill([
                'status'           => CustomerDamageStaging::STATUS_DISPOSED,
                'disposition'      => $row->disposition ?? CustomerDamageStaging::DISPOSITION_CHARGE,
                'disposition_note' => filled($note) ? self::appendNote($row->disposition_note, $note, $userId) : $row->disposition_note,
                'disposed_by'      => $row->disposed_by ?? $userId,
                'disposed_at'      => $row->disposed_at ?? now(),
            ])->save();

            return $row->fresh();
        });
    }

    /**
     * Create Service Ticket — routes through the ST-1 canonical intake service
     * (ServiceTicketIntakeService), the SAME idempotent path the web intake,
     * the Standard-equipment intake, and Warranty intake use, prefilled with
     * the staging context and marked customer_damage_possible. The resulting
     * ticket therefore also appears on the Service Operations Board like any
     * other. Idempotent twice over: the staging row's service_ticket_id is
     * checked under lock, and the intake service dedupes on the staging-scoped
     * idempotency key, so repeated clicks return the one existing ticket.
     */
    public static function disposeServiceTicket(CustomerDamageStaging $staging, int $userId, ?string $note): ServiceTicket
    {
        return DB::transaction(function () use ($staging, $userId, $note) {
            $locked = CustomerDamageStaging::lockForUpdate()->findOrFail($staging->id);

            if ($locked->service_ticket_id) {
                return ServiceTicket::findOrFail($locked->service_ticket_id); // idempotent
            }

            if (!$locked->equipment_id) {
                throw new \RuntimeException(
                    'This record has no equipment reference — service tickets require equipment. Use the Service intake directly.'
                );
            }

            // Canonical creation. service_type is passed explicitly (it is also
            // the intake default); service_location / priority / statuses /
            // opened_at / created_by are applied by the intake service.
            $ticket = ServiceTicketIntakeService::create(
                attributes: [
                    'service_type'             => ServiceType::CustomerDamageRepair->value,
                    'equipment_id'             => $locked->equipment_id,
                    'order_id'                 => $locked->order_id,
                    'customer_id'              => $locked->customer_id,
                    'customer_complaint'       => "Customer damage {$locked->unique_id} ({$locked->source_type}):\n{$locked->observation}",
                    'customer_damage_possible' => true,
                ],
                idempotencyKey: "customer_damage_staging:{$locked->id}",
            );

            $locked->fill([
                'status'            => CustomerDamageStaging::STATUS_DISPOSED,
                'disposition'       => $locked->disposition ?? CustomerDamageStaging::DISPOSITION_SERVICE_TICKET,
                'disposition_note'  => filled($note) ? self::appendNote($locked->disposition_note, $note, $userId) : $locked->disposition_note,
                'disposed_by'       => $locked->disposed_by ?? $userId,
                'disposed_at'       => $locked->disposed_at ?? now(),
                'service_ticket_id' => $ticket->id,
            ])->save();

            return $ticket;
        });
    }

    private static function guardNotDisposed(CustomerDamageStaging $staging): void
    {
        if ($staging->isDisposed()) {
            throw new \RuntimeException('This record was already disposed — refresh to see its current state.');
        }
    }

    private static function appendNote(?string $existing, string $note, int $userId): string
    {
        $stamp = now()->format('M j, Y g:ia');
        $line = "[{$stamp} · user {$userId}] {$note}";

        return filled($existing) ? ($existing . "\n" . $line) : $line;
    }
}

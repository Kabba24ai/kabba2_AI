<?php

namespace App\Http\DataObjects;

use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;

/**
 * Plain PHP value object passed to BillingEngine::charge().
 * Not a Laravel FormRequest — callers construct this from validated controller data.
 */
final class BillingChargeRequest
{
    public function __construct(
        /** The charge type (fuel, damage, extension, etc.) */
        public readonly string $type,

        /**
         * FK → orders.id (the original rental order, not the child extension order).
         * Nullable: Dashboard-level charges (fuel/damage modal) have no order context.
         */
        public readonly ?int $orderId,

        /** FK → customers.id */
        public readonly int $customerId,

        /** Charge amount in dollars */
        public readonly float $amount,

        /** Tax handling flag: add | free | reverse */
        public readonly string $taxType = 'free',

        /** FK → order_products.id — required for checklist-originated charges */
        public readonly ?int $orderProductId = null,

        /** FK → users.id — the person responsible for the charge */
        public readonly ?int $responsiblePersonId = null,

        /** Optional internal note */
        public readonly ?string $notes = null,

        /** Set true for extension charges that need a child Order row created */
        public readonly bool $createChildOrder = false,

        /**
         * Which module created this charge.
         * Use BillingSourceModule enum values.
         * Example: BillingSourceModule::MobileChecklist->value
         */
        public readonly ?string $sourceModule = null,

        /**
         * Which specific event triggered the charge.
         * Use BillingSourceEvent enum values.
         * Example: BillingSourceEvent::ReturnChecklistFuelCharge->value
         */
        public readonly ?string $sourceEvent = null,

        /**
         * Polymorphic source reference type.
         * Example: 'OrderProduct', 'ServiceTicket'
         */
        public readonly ?string $sourceReferenceType = null,

        /**
         * ID of the source record (matches source_reference_type).
         * Example: $orderProduct->id
         */
        public readonly ?int $sourceReferenceId = null,

        /**
         * Arbitrary JSON-serializable context about the charge origin.
         * Mobile fuel example:
         *   ['fuel_initial_reading' => '3/4', 'fuel_final_reading' => '1/4', 'submitted_by_user_id' => 42]
         * Mobile damage example (future):
         *   ['checklist_question_ids' => ['CQ-...'], 'submitted_by_user_id' => 42]
         */
        public readonly ?array $metadata = null,

        /**
         * Caller-supplied idempotency key.
         * If set and a BillingCharge already exists with this key, the existing
         * charge is returned without creating a duplicate. Required for mobile
         * offline sync paths.
         *
         * Recommended format:
         *   "{source_module}:{order_product_id}:{type}:{reading_or_hash}"
         * Example:
         *   "mobile_checklist:42:fuel:1/4"
         */
        public readonly ?string $idempotencyKey = null,
    ) {}

    /**
     * Convenience constructor for mobile return checklist fuel charges.
     * Sets all mobile-specific source fields from the return checklist context.
     */
    public static function mobileReturnFuel(
        int    $orderId,
        int    $customerId,
        int    $orderProductId,
        float  $amount,
        ?int   $submittedByUserId,
        ?string $fuelInitialReading,
        ?string $fuelFinalReading,
    ): self {
        return new self(
            type: BillingChargeType::Fuel->value,
            orderId: $orderId,
            customerId: $customerId,
            amount: $amount,
            taxType: 'free',
            orderProductId: $orderProductId,
            responsiblePersonId: $submittedByUserId,
            sourceModule: BillingSourceModule::MobileChecklist->value,
            sourceEvent: BillingSourceEvent::ReturnChecklistFuelCharge->value,
            sourceReferenceType: 'OrderProduct',
            sourceReferenceId: $orderProductId,
            metadata: [
                'fuel_initial_reading'  => $fuelInitialReading,
                'fuel_final_reading'    => $fuelFinalReading,
                'submitted_by_user_id'  => $submittedByUserId,
            ],
            idempotencyKey: "mobile_checklist:{$orderProductId}:fuel:{$fuelFinalReading}",
        );
    }

    /**
     * Convenience constructor for future mobile return checklist damage charges.
     * This path does not yet exist in SaveReturnController — this constructor
     * is greenfield and should be wired when mobile damage reporting is built.
     */
    public static function mobileReturnDamage(
        int    $orderId,
        int    $customerId,
        int    $orderProductId,
        float  $amount,
        ?int   $submittedByUserId,
        array  $checklistQuestionIds = [],
    ): self {
        return new self(
            type: BillingChargeType::Damage->value,
            orderId: $orderId,
            customerId: $customerId,
            amount: $amount,
            taxType: 'free',
            orderProductId: $orderProductId,
            responsiblePersonId: $submittedByUserId,
            sourceModule: BillingSourceModule::MobileChecklist->value,
            sourceEvent: BillingSourceEvent::ReturnChecklistDamageCharge->value,
            sourceReferenceType: 'OrderProduct',
            sourceReferenceId: $orderProductId,
            metadata: [
                'checklist_question_ids' => $checklistQuestionIds,
                'submitted_by_user_id'   => $submittedByUserId,
            ],
            idempotencyKey: "mobile_checklist:{$orderProductId}:damage",
        );
    }
}

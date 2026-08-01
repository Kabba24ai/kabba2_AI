<?php

namespace App\Services\ChecklistManagement;

use RuntimeException;

/**
 * Domain error from the canonical Rental Ready inspection writer. Carries a
 * stable `code` so the web and mobile controllers can map one shared failure
 * to their own response shape.
 */
class RentalReadyInspectionException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode, public readonly array $context = [])
    {
        parent::__construct($message);
    }

    public static function equipmentRented(int $equipmentId, ?string $status): self
    {
        return new self(
            'Rental Ready cannot be recorded while the equipment is actively rented to a customer.',
            'EQUIPMENT_RENTED',
            ['equipment_id' => $equipmentId, 'current_equipment_status' => $status],
        );
    }

    public static function noChecklist(int $equipmentId): self
    {
        return new self('No Rental Ready checklist is assigned to this equipment.', 'NO_CHECKLIST', ['equipment_id' => $equipmentId]);
    }

    public static function noQuestions(int $equipmentId): self
    {
        return new self('The assigned Rental Ready checklist has no questions.', 'NO_QUESTIONS', ['equipment_id' => $equipmentId]);
    }

    public static function inspectionFinalized(string $uniqueId): self
    {
        return new self('This inspection is finalized and can no longer be edited. Start a new inspection instead.', 'INSPECTION_FINALIZED', ['inspection_uuid' => $uniqueId]);
    }
}

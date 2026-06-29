<?php

namespace App\Enums\Api;

enum ApiErrorCode: string
{
    // Equipment state
    case EquipmentCurrentlyRented  = 'EQUIPMENT_CURRENTLY_RENTED';
    case InvalidEquipmentStatus    = 'INVALID_EQUIPMENT_STATUS';
    case EquipmentNotFound         = 'EQUIPMENT_NOT_FOUND';

    // Order / checklist state
    case OrderProductNotFound      = 'ORDER_PRODUCT_NOT_FOUND';
    case ChecklistAlreadySubmitted = 'CHECKLIST_ALREADY_SUBMITTED';
    case NoChecklistFound          = 'NO_CHECKLIST_FOUND';
    case NoQuestionsFound          = 'NO_QUESTIONS_FOUND';

    // People
    case UserNotFound              = 'USER_NOT_FOUND';

    public function httpStatus(): int
    {
        return match($this) {
            self::EquipmentCurrentlyRented,
            self::InvalidEquipmentStatus    => 403,
            self::ChecklistAlreadySubmitted => 409,
            default                         => 404,
        };
    }

    public function translationKey(): string
    {
        return match($this) {
            self::EquipmentCurrentlyRented  => 'messages.api.admin.v1.rental_ready_checklists.invalid_equipment_status',
            self::InvalidEquipmentStatus    => 'messages.api.admin.v1.orders.equipment_status',
            self::EquipmentNotFound         => 'messages.api.admin.v1.orders.no_equipment_found',
            self::OrderProductNotFound      => 'messages.api.admin.v1.orders.no_order_product_found',
            self::ChecklistAlreadySubmitted => 'messages.api.admin.v1.orders.checklist_already_exists',
            self::NoChecklistFound          => 'messages.api.admin.v1.rental_ready_checklists.no_rental_ready_checklist_found',
            self::NoQuestionsFound          => 'messages.api.admin.v1.customer_checklists.no_questions_found',
            self::UserNotFound              => 'messages.api.admin.v1.users.no_users_found',
        };
    }
}

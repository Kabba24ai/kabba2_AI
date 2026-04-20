<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AIAssignmentRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AIAssignmentRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AIAssignmentRule query()
 */
	class AIAssignmentRule extends \Eloquent {}
}

namespace App\Models\Authrise{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone_number
 * @property string $email
 * @property string|null $password
 * @property string|null $business_name
 * @property string|null $street_address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $card_name
 * @property string|null $card_last_four
 * @property string|null $card_expiry
 * @property string|null $card_brand
 * @property string|null $customer_profile_id
 * @property string|null $payment_profile_id
 * @property string $status
 * @property string $setup_status
 * @property string|null $comment
 * @property numeric $amount
 * @property \Illuminate\Support\Carbon|null $schedule_datetime
 * @property string|null $response_message
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCardBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCardExpiry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCardLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission wherePaymentProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereResponseMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereScheduleDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereSetupStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereStreetAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Submission withoutTrashed()
 */
	class Submission extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\ChecklistMaster{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $checklist_system_name
 * @property int|null $equipment_category_id
 * @property int|null $rental_ready_template_id
 * @property int|null $customer_admin_template_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\ProductManagement\ProductCategory|null $category
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate|null $customerAdminTemplate
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate|null $rentalReadyTemplate
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereChecklistSystemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereCustomerAdminTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereEquipmentCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereRentalReadyTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChecklistMaster withoutTrashed()
 */
	class ChecklistMaster extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\CustomerAdmin{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $category_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion> $questions
 * @property-read int|null $questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminCategory whereUpdatedAt($value)
 */
	class CustomerAdminCategory extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\CustomerAdmin{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $question_name
 * @property int $category_id
 * @property string $question_delivery_text
 * @property string $question_return_text
 * @property int $required_question
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer> $answers
 * @property-read int|null $answers_count
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion> $templateQuestions
 * @property-read int|null $template_questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereQuestionDeliveryText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereQuestionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereQuestionReturnText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereRequiredQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestion whereUpdatedAt($value)
 */
	class CustomerAdminQuestion extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\CustomerAdmin{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $answer_delivery_text
 * @property string $answer_return_text
 * @property numeric $delivery_amt
 * @property numeric $return_amt
 * @property int $required
 * @property int $is_damaged
 * @property int $sync_texts If true, delivery and return texts are synced
 * @property string|null $answer_sync_map Maps answer index to sync status {0: true, 1: false}
 * @property int $question_id
 * @property int $index_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion $question
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereAnswerDeliveryText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereAnswerReturnText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereAnswerSyncMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereDeliveryAmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereIsDamaged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereReturnAmt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereSyncTexts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminQuestionAnswer whereUpdatedAt($value)
 */
	class CustomerAdminQuestionAnswer extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\CustomerAdmin{
/**
 * @property int $id
 * @property string $template_name
 * @property string|null $description
 * @property string|null $equipment_category_id
 * @property int $active_template
 * @property string $unique_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\ProductCategory|null $equipmentCategory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion> $questions
 * @property-read int|null $questions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplateQuestion> $templateQuestions
 * @property-read int|null $template_questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereActiveTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereEquipmentCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplate whereUpdatedAt($value)
 */
	class CustomerAdminTemplate extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\CustomerAdmin{
/**
 * @property int $id
 * @property int $template_id
 * @property int $question_id
 * @property int $index_number
 * @property string $unique_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion $question
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAdminTemplateQuestion whereUpdatedAt($value)
 */
	class CustomerAdminTemplateQuestion extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\EquipmentChecklist{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $equipment_rental_ready_template_id
 * @property int|null $rental_ready_checklist_questions_id
 * @property int|null $selected_answer_id
 * @property string $rental_ready_qa_json
 * @property string|null $general_notes
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion|null $question
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer|null $selectedAnswer
 * @property-read \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereEquipmentRentalReadyTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereGeneralNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereRentalReadyChecklistQuestionsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereRentalReadyQaJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereSelectedAnswerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestion withoutTrashed()
 */
	class EquipmentRentalReadyChecklistQuestion extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\EquipmentChecklist{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $equipment_rental_ready_template_id
 * @property string $rental_ready_all_qa_json
 * @property int|null $action_by
 * @property string $action_user_name
 * @property string|null $inspector_name
 * @property string|null $inspection_date
 * @property numeric|null $equipment_hours
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\User|null $actionUser
 * @property-read \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereActionBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereActionUserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereEquipmentHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereEquipmentRentalReadyTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereInspectionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereInspectorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereRentalReadyAllQaJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyChecklistQuestionLog withoutTrashed()
 */
	class EquipmentRentalReadyChecklistQuestionLog extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\EquipmentChecklist{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $equipment_id
 * @property int|null $employee_id
 * @property string $employee_name
 * @property int|null $order_id
 * @property int|null $order_product_id
 * @property string $inspection_date
 * @property string $inspection_time
 * @property numeric|null $equipment_hours
 * @property string|null $general_notes
 * @property string $status
 * @property int $is_complete
 * @property int $total_questions
 * @property int $required_questions
 * @property int $optional_questions
 * @property int $required_items_completed
 * @property int $items_requiring_maintenance
 * @property int $damaged_items
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestion> $checklistQuestions
 * @property-read int|null $checklist_questions_count
 * @property-read \App\Models\Iam\Personnel\User|null $createdBy
 * @property-read \App\Models\Iam\Personnel\User|null $employee
 * @property-read \App\Models\MaintenanceManagement\Equipment|null $equipment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestionLog> $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @property-read \App\Models\Iam\Personnel\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereDamagedItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereEmployeeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereEquipmentHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereGeneralNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereInspectionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereInspectionTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereIsComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereItemsRequiringMaintenance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereOptionalQuestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereRequiredItemsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereRequiredQuestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereTotalQuestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentRentalReadyTemplate withoutTrashed()
 */
	class EquipmentRentalReadyTemplate extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\EquipmentChecklist{
/**
 * @property int $id
 * @property int $equipment_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $changed_by
 * @property \Illuminate\Support\Carbon $changed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MaintenanceManagement\Equipment|null $equipment
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereChangedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereFromStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereToStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentStatusLog whereUpdatedAt($value)
 */
	class EquipmentStatusLog extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\RentalReady{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $category_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion> $questions
 * @property-read int|null $questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistCategory withoutTrashed()
 */
	class RentalReadyChecklistCategory extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\RentalReady{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $question_name
 * @property int $category_id
 * @property int $required_question
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer> $answers
 * @property-read int|null $answers_count
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion> $templateQuestions
 * @property-read int|null $template_questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereQuestionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereRequiredQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestion withoutTrashed()
 */
	class RentalReadyChecklistQuestion extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\RentalReady{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $answer_name
 * @property int $question_id
 * @property string $type
 * @property int $index_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion|null $question
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereAnswerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistQuestionAnswer withoutTrashed()
 */
	class RentalReadyChecklistQuestionAnswer extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\RentalReady{
/**
 * @property int $id
 * @property string $template_name
 * @property string|null $description
 * @property int $active_template
 * @property string $unique_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $equipment_category_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\ProductManagement\ProductCategory|null $equipmentCategory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion> $questions
 * @property-read int|null $questions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplateQuestion> $templateQuestions
 * @property-read int|null $template_questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereActiveTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereEquipmentCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplate withoutTrashed()
 */
	class RentalReadyChecklistTemplate extends \Eloquent {}
}

namespace App\Models\ChecklistManagement\RentalReady{
/**
 * @property int $id
 * @property int $template_id
 * @property int $question_id
 * @property int $index_number
 * @property string $unique_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion|null $question
 * @property-read \App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RentalReadyChecklistTemplateQuestion withoutTrashed()
 */
	class RentalReadyChecklistTemplateQuestion extends \Eloquent {}
}

namespace App\Models\Clients{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string $code
 * @property string|null $api_url Base URL for the client API
 * @property string|null $admin_url Base URL for the client admin panel
 * @property string|null $front_url Base URL for the client front panel
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereAdminUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereApiUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereFrontUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 */
	class Client extends \Eloquent {}
}

namespace App\Models\Configurations{
/**
 * @property int $id
 * @property string $title
 * @property string $hash_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereHashCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Color whereUpdatedAt($value)
 */
	class Color extends \Eloquent {}
}

namespace App\Models\Configurations{
/**
 * @property int $id
 * @property string $section_key
 * @property string $title
 * @property string $content
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereSectionKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunitiesSiteContent whereUpdatedAt($value)
 */
	class OpportunitiesSiteContent extends \Eloquent {}
}

namespace App\Models\Configurations{
/**
 * @property int $id
 * @property string $unique_id
 * @property string|null $setting_type
 * @property int $sort_order
 * @property string $setting_name
 * @property string|null $setting_title
 * @property string|null $value_type
 * @property string|null $setting_value
 * @property string|null $placeholder
 * @property int $is_secure_field
 * @property int $is_eye_toggle
 * @property int $is_required
 * @property int $is_encrypted
 * @property string|null $setting_options
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsEyeToggle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsSecureField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting wherePlaceholder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereSettingName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereSettingOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereSettingTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereSettingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereSettingValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValueType($value)
 */
	class Setting extends \Eloquent {}
}

namespace App\Models\Configurations{
/**
 * @property int $id
 * @property array<array-key, mixed> $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeTrackerSetting whereUpdatedAt($value)
 */
	class TimeTrackerSetting extends \Eloquent {}
}

namespace App\Models\Configurations{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $phone
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $source
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationSetting whereUserId($value)
 */
	class UserNotificationSetting extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $company_name
 * @property string|null $company_website
 * @property string|null $company_phone
 * @property string|null $email
 * @property string|null $password
 * @property string|null $password_reset_token
 * @property string|null $password_reset_token_expiry
 * @property string|null $remember_token
 * @property int|null $media_id
 * @property string|null $phone
 * @property string|null $dob
 * @property string|null $authorize_profile_id
 * @property string $status
 * @property int $is_reset
 * @property int $is_guest
 * @property string $tax_status
 * @property int|null $tax_document_media_id
 * @property string|null $tax_document_upload_date
 * @property string|null $tax_document_valid_until
 * @property string|null $tax_document_status
 * @property string|null $tax_document_type
 * @property int $tax_status_approved_by
 * @property int $is_credit_account
 * @property int $same_as_billing
 * @property string|null $tags
 * @property numeric|null $credit_limit
 * @property string|null $available_credit_balance
 * @property int $account_approved_by
 * @property string|null $account_application_completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $current_otp
 * @property string|null $last_otp_sent_at
 * @property int|null $license_front_media_id
 * @property int|null $license_back_media_id
 * @property string|null $license_expiry_date
 * @property-read \App\Models\Iam\Personnel\User|null $accountApprovedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\CustomerAccount> $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\CustomerAddress> $addresses
 * @property-read int|null $addresses_count
 * @property-read \App\Models\Customers\CustomerAddress|null $billingAddress
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\CustomerCard> $cards
 * @property-read int|null $cards_count
 * @property-read mixed $days_since_last_payment
 * @property-read string $full_name
 * @property-read bool $is_tax_exempt_valid
 * @property-read mixed $last_payment
 * @property-read mixed $paid_sales
 * @property-read mixed $payment_status_badge
 * @property-read mixed $pending_sales
 * @property-read mixed $tag_objects
 * @property-read mixed $tags_array
 * @property-read mixed $total_account_order_amount
 * @property-read mixed $total_order_amount
 * @property-read mixed $total_overdue_invoices
 * @property-read mixed $total_paid_invoices
 * @property-read mixed $total_pending_invoices
 * @property-read mixed $unpaid_invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \App\Models\Customers\Invoice|null $latestInvoice
 * @property-read \App\Models\Global\Media|null $licenseBack
 * @property-read \App\Models\Global\Media|null $licenseFront
 * @property-read \App\Models\Global\Media|null $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\CustomerNote> $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\CustomerAccount> $paymentAccounts
 * @property-read int|null $payment_accounts_count
 * @property-read \App\Models\Customers\CustomerAddress|null $shippingAddress
 * @property-read \App\Models\Iam\Personnel\User|null $taxStatusApprovedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAccountApplicationCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAccountApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAuthorizeProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAvailableCreditBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreditLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCurrentOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsCreditAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsGuest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsReset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastOtpSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLicenseBackMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLicenseExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLicenseFrontMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePasswordResetToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePasswordResetTokenExpiry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereSameAsBilling($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxDocumentMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxDocumentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxDocumentUploadDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxDocumentValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTaxStatusApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 */
	class Customer extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $customer_id
 * @property int|null $order_id
 * @property numeric $balance
 * @property numeric $amount
 * @property \App\Enums\Customers\PaymentMethod $payment_type
 * @property int|null $responsible_person_id
 * @property string|null $responsible_person_name
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $payment_number_id
 * @property string|null $auth_code
 * @property string|null $customer_profile_id
 * @property string|null $payment_profile_id
 * @property string|null $reason
 * @property string $sales_tax
 * @property string|null $sales_tax_type
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $invoice_id
 * @property int|null $invoice_item_id
 * @property-read \App\Models\Customers\CustomerCard|null $card
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read \App\Models\Customers\Invoice|null $invoice
 * @property-read \App\Models\Orders\Order|null $order
 * @property-read \App\Models\Iam\Personnel\User|null $responsibleUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount invoiceEntries()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereAuthCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereInvoiceItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount wherePaymentNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount wherePaymentProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereResponsiblePersonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereResponsiblePersonName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereSalesTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereSalesTaxType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAccount whereUpdatedAt($value)
 */
	class CustomerAccount extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $type
 * @property int $customer_id
 * @property int $is_primary
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $state_id
 * @property string|null $country
 * @property string|null $city
 * @property string|null $zip_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $email
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read mixed $full_address
 * @property-read mixed $full_name
 * @property-read mixed $state_name
 * @property-read \App\Models\Locations\State|null $state
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress primary()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerAddress whereZipCode($value)
 */
	class CustomerAddress extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $customer_id
 * @property string|null $payment_profile_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $card_number
 * @property string|null $card_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read mixed $card_expiry
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereCardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard wherePaymentProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerCard whereUpdatedAt($value)
 */
	class CustomerCard extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $customer_id
 * @property int|null $created_by
 * @property string|null $description
 * @property string|null $created_date
 * @property string|null $created_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereCreatedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereCreatedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerNote whereUpdatedAt($value)
 */
	class CustomerNote extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailCategory whereUpdatedAt($value)
 */
	class EmailCategory extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $invoice_number
 * @property string|null $payment_method
 * @property string $invoice_date
 * @property string|null $due_date
 * @property int $customer_id
 * @property int|null $task_id
 * @property int $invoice_created_by
 * @property numeric $subtotal
 * @property numeric $sales_tax
 * @property numeric $total
 * @property numeric $paid_amount
 * @property numeric $open_amount
 * @property string|null $invoice_notes
 * @property string $invoice_status
 * @property string $is_email_send
 * @property string $is_mail
 * @property string|null $is_mail_date
 * @property string|null $mail_send_at
 * @property string|null $payment_number_id
 * @property string|null $auth_code
 * @property string|null $customer_profile_id
 * @property string|null $payment_profile_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $invoice_type
 * @property-read \App\Models\Iam\Personnel\User|null $creator
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\InvoiceItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereAuthCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIsEmailSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIsMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIsMailDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereMailSendAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereOpenAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSalesTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property int $invoice_type 0 = Kaaba2, 1 = Project Manager
 * @property int $invoice_id
 * @property string $type
 * @property string $item_name
 * @property string|null $item_id
 * @property int $qty
 * @property string|null $sku
 * @property numeric $unit
 * @property numeric $tax
 * @property numeric $total
 * @property array<array-key, mixed>|null $extras
 * @property string|null $notes
 * @property string|null $reference
 * @property int|null $responsible_person_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\Invoice $invoice
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @property-read \App\Models\Iam\Personnel\User|null $responsiblePerson
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereExtras($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereInvoiceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereResponsiblePersonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUpdatedAt($value)
 */
	class InvoiceItem extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $invoice_id
 * @property int|null $order_id
 * @property int $customer_id
 * @property int|null $receipt_created_by
 * @property string|null $payment_method
 * @property string|null $receipt_date
 * @property string|null $order_date
 * @property string $payment_status
 * @property numeric $subtotal
 * @property numeric $sales_tax
 * @property numeric $total
 * @property string $is_email_status
 * @property string|null $mail_send_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\User|null $createdBy
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read \App\Models\Customers\Invoice|null $invoice
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\ReceiptItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereIsEmailStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereMailSendAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReceiptCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereSalesTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereUpdatedAt($value)
 */
	class Receipt extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property int $receipt_id
 * @property string|null $item_id
 * @property string $type
 * @property string $item_name
 * @property numeric $unit
 * @property int $qty
 * @property numeric $tax
 * @property numeric $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @property-read \App\Models\Customers\Receipt $receipt
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReceiptItem whereUpdatedAt($value)
 */
	class ReceiptItem extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $funnel_name
 * @property string|null $description
 * @property int|null $sales_funnel_category_id
 * @property string|null $trigger_event
 * @property string|null $trigger_event_timing
 * @property string|null $date_value
 * @property string|null $hour_value
 * @property string|null $minute_value
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\SalesFunnelCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProductFunnelLog> $funnelLogs
 * @property-read int|null $funnel_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\SalesFunnelSteps> $steps
 * @property-read int|null $steps_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel afterEvent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel beforeEvent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereDateValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereFunnelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereHourValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereMinuteValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereSalesFunnelCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereTriggerEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereTriggerEventTiming($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnel whereUpdatedAt($value)
 */
	class SalesFunnel extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $category_name
 * @property string|null $description
 * @property string|null $color_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\SalesFunnel> $funnels
 * @property-read int|null $funnels_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereColorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelCategory whereUpdatedAt($value)
 */
	class SalesFunnelCategory extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property int $sales_funnel_id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\Product $product
 * @property-read \App\Models\Customers\SalesFunnel $salesFunnel
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts whereSalesFunnelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelProducts whereUpdatedAt($value)
 */
	class SalesFunnelProducts extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $sales_funnel_id
 * @property string $step_type
 * @property int|null $sms_category_id
 * @property int|null $sms_message_id
 * @property string|null $name
 * @property string|null $message
 * @property string $delay_unit
 * @property int $delay_value
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\SalesFunnel $salesFunnel
 * @property-read \App\Models\Customers\SmsCategory|null $smsCategory
 * @property-read \App\Models\Customers\SmsFunnel|null $smsMessage
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereDelayUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereDelayValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereSalesFunnelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereSmsCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereSmsMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereStepType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesFunnelSteps whereUpdatedAt($value)
 */
	class SalesFunnelSteps extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property int $sms_cat_id
 * @property string $name
 * @property string|null $description
 * @property string|null $send_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\SmsCategory $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereSendDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereSmsCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsBroadcast whereUpdatedAt($value)
 */
	class SmsBroadcast extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\SmsBroadcast> $broadcasts
 * @property-read int|null $broadcasts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsCategory whereUpdatedAt($value)
 */
	class SmsCategory extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property int $sms_cat_id
 * @property string $name
 * @property string|null $description
 * @property string $sales_funnels
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $status
 * @property-read \App\Models\Customers\SmsCategory $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereSalesFunnels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereSmsCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SmsFunnel whereUpdatedAt($value)
 */
	class SmsFunnel extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 */
	class Tag extends \Eloquent {}
}

namespace App\Models\Global{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $asset_type
 * @property string|null $model_type
 * @property int|null $model_id
 * @property string|null $folder_name
 * @property string $file_name
 * @property string|null $original_file_name
 * @property string|null $file_extension
 * @property string|null $file_type
 * @property string|null $mime_type
 * @property float|null $file_size
 * @property string|null $signed_url
 * @property string|null $download_signed_url
 * @property string $is_used
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereAssetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereDownloadSignedUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFileExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFolderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereIsUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereOriginalFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereSignedUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUpdatedAt($value)
 */
	class Media extends \Eloquent {}
}

namespace App\Models\Global{
/**
 * @property int $id
 * @property string $request_type
 * @property string $status
 * @property array<array-key, mixed>|null $sent_data
 * @property array<array-key, mixed>|null $received_data
 * @property string|null $error_message
 * @property string|null $error_code
 * @property int|null $prompt_tokens
 * @property int|null $completion_tokens
 * @property int|null $total_tokens
 * @property int|null $response_time_ms
 * @property string|null $openai_request_id
 * @property string|null $model
 * @property string|null $endpoint
 * @property string|null $ip_address
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Orders\Order|null $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog byModel($model)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog byRequestType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog dateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog successful()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereCompletionTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereOpenaiRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog wherePromptTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereReceivedData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereResponseTimeMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereSentData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereTotalTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenAILog whereUpdatedAt($value)
 */
	class OpenAILog extends \Eloquent {}
}

namespace App\Models\Global{
/**
 * @property int $id
 * @property \App\Enums\Communication\SmsType|null $sms_type
 * @property string $status
 * @property string $phone
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $sms_sent_at
 * @property int|null $customer_id
 * @property int|null $order_product_id
 * @property int|null $order_id
 * @property string|null $twilio_sid
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\Customer|null $customer
 * @property-read \App\Models\Orders\Order|null $order
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog byCustomer($customerId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog byOrder($orderId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog byType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog sent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereSmsSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereSmsType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereTwilioSid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SMSLog whereUpdatedAt($value)
 */
	class SMSLog extends \Eloquent {}
}

namespace App\Models\Iam\AccessControl{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $module_category_id
 * @property string|null $title
 * @property string|null $name
 * @property string|null $model_name
 * @property string|null $permission_names
 * @property string|null $permission_options
 * @property string $need_set_permissions
 * @property string|null $permission_updated_at
 * @property int|null $permission_updated_user_id
 * @property int|null $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\AccessControl\ModuleCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\AccessControl\Permission> $module_permissions
 * @property-read int|null $module_permissions_count
 * @property-read \App\Models\Iam\Personnel\User|null $permission_updated_user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereModuleCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereNeedSetPermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module wherePermissionNames($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module wherePermissionOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module wherePermissionUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module wherePermissionUpdatedUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereUpdatedAt($value)
 */
	class Module extends \Eloquent {}
}

namespace App\Models\Iam\AccessControl{
/**
 * @property int $id
 * @property string $unique_id
 * @property string|null $title
 * @property int|null $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\AccessControl\Module> $modules
 * @property-read int|null $modules_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleCategory whereUpdatedAt($value)
 */
	class ModuleCategory extends \Eloquent {}
}

namespace App\Models\Iam\AccessControl{
/**
 * @property int $id
 * @property int|null $module_id
 * @property string $name
 * @property string|null $title
 * @property string $guard_name
 * @property string $permission_to_all
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\AccessControl\Module|null $module
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\AccessControl\RoleHasPermission> $permission_roles
 * @property-read int|null $permission_roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission wherePermissionToAll($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models\Iam\AccessControl{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string $guard_name
 * @property string|null $short_name
 * @property string $status
 * @property string|null $color
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 */
	class Role extends \Eloquent {}
}

namespace App\Models\Iam\AccessControl{
/**
 * @property int $permission_id
 * @property int $role_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleHasPermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleHasPermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleHasPermission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleHasPermission wherePermissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleHasPermission whereRoleId($value)
 */
	class RoleHasPermission extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property string $goal_name
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $description
 * @property string $goal_type
 * @property int $days_missed_max
 * @property int $days_late_max
 * @property int $is_active
 * @property int $display_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereDaysLateMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereDaysMissedMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereGoalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereGoalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AchievementGoal whereUpdatedAt($value)
 */
	class AchievementGoal extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $attendance_date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $check_in_time
 * @property int $minutes_late
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereAttendanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereCheckInTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereMinutesLate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceRecord whereUpdatedAt($value)
 */
	class AttendanceRecord extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $user_id
 * @property int $contact_index
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $mobile_phone
 * @property string|null $phone_number
 * @property string|null $street_address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereContactIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereMiddleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereMobilePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereStreetAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmergencyContact whereZipCode($value)
 */
	class EmergencyContact extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $clock_in
 * @property \Illuminate\Support\Carbon|null $clock_out
 * @property int $break_duration Break duration in minutes
 * @property string|null $notes
 * @property string $status
 * @property numeric $total_hours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\TimeEntryBreak|null $activeBreak
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\TimeEntryBreak> $breaks
 * @property-read int|null $breaks_count
 * @property-read \App\Models\Iam\Personnel\User|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereBreakDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntry whereUpdatedAt($value)
 */
	class TimeEntry extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $time_entry_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\TimeEntry $timeEntry
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereTimeEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TimeEntryBreak whereUpdatedAt($value)
 */
	class TimeEntryBreak extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $employee_code
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string $email
 * @property string|null $mobile_phone
 * @property string|null $phone_number
 * @property string|null $street_address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string|null $pay_type
 * @property int|null $store_id
 * @property int $limit_start_time
 * @property int $limit_end_time
 * @property int $lunch_override
 * @property string|null $auto_clockout_penalty
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $avatar
 * @property string $status
 * @property string|null $shift_start_time
 * @property string|null $shift_end_time
 * @property int $vacation_eligible
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $vacation_allotment_hour_id
 * @property int|null $vacation_start_day_id
 * @property string|null $social_security
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Iam\Personnel\TimeEntry|null $activeTimeEntry
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\VacationRequest> $approvedVacationRequests
 * @property-read int|null $approved_vacation_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\UserDevice> $devices
 * @property-read int|null $devices_count
 * @property-read \App\Models\Iam\Personnel\EmergencyContact|null $emergencyContactOne
 * @property-read \App\Models\Iam\Personnel\EmergencyContact|null $emergencyContactTwo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\EmergencyContact> $emergencyContacts
 * @property-read int|null $emergency_contacts_count
 * @property-read string $full_name
 * @property-read array $role_short_names
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\Locations\State|null $stateRelation
 * @property-read \App\Models\Stores\Store|null $store
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\TimeEntry> $timeEntries
 * @property-read int|null $time_entries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\Models\Iam\Personnel\VacationHour|null $vacationAllotmentHour
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\VacationRequest> $vacationRequests
 * @property-read int|null $vacation_requests_count
 * @property-read \App\Models\Iam\Personnel\VacationDay|null $vacationStartDay
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User activeOrIds($ids = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAutoClockoutPenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmployeeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLimitEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLimitStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLunchOverride($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMiddleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMobilePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereShiftEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereShiftStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSocialSecurity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStreetAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVacationAllotmentHourId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVacationEligible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVacationStartDayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $user_id
 * @property string $device_token
 * @property string|null $fcm_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereDeviceToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereFcmToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserId($value)
 */
	class UserDevice extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property string $unique_id
 * @property string|null $title
 * @property string|null $type
 * @property string|null $body
 * @property string|null $params
 * @property int $user_id
 * @property int|null $order_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Orders\Order|null $order
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotification whereUserId($value)
 */
	class UserNotification extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property string $name
 * @property int $day_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay whereDayNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationDay whereUpdatedAt($value)
 */
	class VacationDay extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property string $name
 * @property int $hours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationHour whereUpdatedAt($value)
 */
	class VacationHour extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $vacation_request_hour_id
 * @property string $request_type
 * @property string $status
 * @property string|null $notes
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $denial_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\User|null $approver
 * @property-read \App\Models\Iam\Personnel\User|null $employee
 * @property-read float $hours
 * @property-read \App\Models\Iam\Personnel\VacationRequestHour $hourOption
 * @property-read \App\Models\Iam\Personnel\VacationRequestHour $requestHour
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereDenialReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequest whereVacationRequestHourId($value)
 */
	class VacationRequest extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property string $name
 * @property numeric $hours
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\VacationRequest> $vacationRequests
 * @property-read int|null $vacation_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VacationRequestHour whereUpdatedAt($value)
 */
	class VacationRequestHour extends \Eloquent {}
}

namespace App\Models\Iam\Personnel{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $store_id
 * @property string $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int $is_scheduled
 * @property numeric $hours
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Stores\Store|null $store
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereIsScheduled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereUserId($value)
 */
	class WorkSchedule extends \Eloquent {}
}

namespace App\Models\Locations{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $abbreviation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State order($direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereAbbreviation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State whereUpdatedAt($value)
 */
	class State extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $equipment_name
 * @property int|null $product_category_id
 * @property string $equipment_id
 * @property numeric|null $equipment_hours
 * @property int|null $store_id
 * @property string|null $is_tracked
 * @property numeric|null $overage_rate
 * @property string $brand
 * @property string|null $model
 * @property string|null $model_year
 * @property int $not_for_rent
 * @property string|null $date_acquired
 * @property \App\Enums\Equipments\EquipmentKeyStartingMechanism|null $key_starting_mechanism
 * @property numeric|null $equipment_value
 * @property string|null $coi_submitted
 * @property numeric|null $purchase_cost
 * @property numeric|null $freight_shipping
 * @property numeric|null $taxes_fees
 * @property numeric|null $down_payment
 * @property numeric|null $amount_financed
 * @property string|null $ownership_type
 * @property string|null $finance_company
 * @property int|null $term_in_months
 * @property numeric|null $interest_rate
 * @property numeric|null $monthly_payment
 * @property string|null $vehicle_identification_number
 * @property string|null $serial_number
 * @property string|null $license_plate
 * @property string|null $imei
 * @property int|null $warranty_duration_months
 * @property int|null $warranty_duration_hours
 * @property \App\Enums\Equipments\EquipmentPowerSourceType|null $power_source_type
 * @property array<array-key, mixed>|null $volts
 * @property array<array-key, mixed>|null $amps
 * @property string $has_def
 * @property numeric|null $diesel_tank_capacity
 * @property numeric|null $def_tank_capacity
 * @property numeric|null $gas_tank_capacity
 * @property numeric|null $standard_battery_count
 * @property numeric|null $expanded_battery_count
 * @property int|null $checklist_master_id
 * @property int|null $equipment_service_id
 * @property int $bring_service_flag
 * @property numeric|null $bring_service_hour
 * @property string|null $equipment_notes
 * @property \App\Enums\Equipments\EquipmentCurrentStatus $current_status
 * @property string|null $current_status_changed_at
 * @property int|null $current_order_id
 * @property int|null $current_order_product_id
 * @property int|null $current_status_updated_by
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $parts_list_id
 * @property-read \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate|null $activeEquipmentRentalReadyTemplate
 * @property-read \App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster|null $checklistMaster
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate|null $customerAdminTemplates
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\EquipmentMedia> $documentImages
 * @property-read int|null $document_images_count
 * @property-read mixed $category_name
 * @property-read string $last_inspection
 * @property-read string $status_label
 * @property-read \App\Models\Orders\OrderProduct|null $lastOrderProduct
 * @property-read \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate|null $lastRentalReadyTemplate
 * @property-read \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate|null $latestRentalReadyTemplate
 * @property-read \App\Models\Orders\Order|null $order
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProduct> $orderProducts
 * @property-read int|null $order_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProduct> $overdueOrderProducts
 * @property-read int|null $overdue_order_products_count
 * @property-read \App\Models\MaintenanceManagement\PartsList|null $partsList
 * @property-read \App\Models\ProductManagement\ProductCategory|null $productCategory
 * @property-read \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate|null $serviceTemplate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\EquipmentSoftAssign> $softAssignments
 * @property-read int|null $soft_assignments_count
 * @property-read \App\Models\Iam\Personnel\User|null $statusUpdatedByUser
 * @property-read \App\Models\Stores\Store|null $store
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment notRented()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereAmountFinanced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereAmps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereBringServiceFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereBringServiceHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereChecklistMasterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCoiSubmitted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCurrentOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCurrentOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCurrentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCurrentStatusChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereCurrentStatusUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDateAcquired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDefTankCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDieselTankCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereDownPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereEquipmentValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereExpandedBatteryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereFinanceCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereFreightShipping($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereGasTankCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereHasDef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereImei($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereInterestRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereIsTracked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereKeyStartingMechanism($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereLicensePlate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereModelYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereMonthlyPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereNotForRent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereOverageRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereOwnershipType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment wherePartsListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment wherePowerSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment wherePurchaseCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereStandardBatteryCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereTaxesFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereTermInMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereVehicleIdentificationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereVolts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereWarrantyDurationHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment whereWarrantyDurationMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipment withoutTrashed()
 */
	class Equipment extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $equipment_id
 * @property int $media_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MaintenanceManagement\Equipment|null $equipment
 * @property-read \App\Models\Global\Media $media
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentMedia whereUpdatedBy($value)
 */
	class EquipmentMedia extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property int $equipment_id
 * @property int $order_id
 * @property int $order_product_id
 * @property int|null $assigned_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MaintenanceManagement\Equipment|null $equipment
 * @property-read \App\Models\Orders\Order $order
 * @property-read \App\Models\Orders\OrderProduct $orderProduct
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipmentSoftAssign whereUpdatedAt($value)
 */
	class EquipmentSoftAssign extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $interval_type
 * @property array<array-key, mixed> $intervals
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate> $templates
 * @property-read int|null $templates_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereIntervalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereIntervals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntervalPreset whereUpdatedAt($value)
 */
	class IntervalPreset extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property int $parts_list_id
 * @property int $part_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart wherePartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart wherePartsListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ListsPart whereUpdatedAt($value)
 */
	class ListsPart extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $part_name
 * @property string|null $primary_part_number
 * @property string|null $primary_part_supplier_id
 * @property int|null $primary_brand_id
 * @property numeric|null $primary_part_cost
 * @property int $stock_level
 * @property int $min_stock
 * @property bool $dni
 * @property int|null $part_category_id
 * @property bool $general_supply_item
 * @property string|null $description
 * @property string|null $alt_1_part_number
 * @property numeric|null $alt_1_part_cost
 * @property string|null $alt_1_part_supplier_id
 * @property int|null $alt_1_brand_id
 * @property string|null $alt_2_part_number
 * @property numeric|null $alt_2_part_cost
 * @property string|null $alt_2_part_supplier_id
 * @property int|null $alt_2_brand_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MaintenanceManagement\PartBrand|null $alt1Brand
 * @property-read \App\Models\MaintenanceManagement\Supplier|null $alt1Supplier
 * @property-read \App\Models\MaintenanceManagement\PartBrand|null $alt2Brand
 * @property-read \App\Models\MaintenanceManagement\Supplier|null $alt2Supplier
 * @property-read \App\Models\MaintenanceManagement\PartCategory|null $category
 * @property-read mixed $all_brand_names
 * @property-read mixed $all_supplier_names
 * @property-read mixed $assigned
 * @property-read mixed $assigned_equipment_names
 * @property-read mixed $stock_status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\PartsList> $partsLists
 * @property-read int|null $parts_lists_count
 * @property-read \App\Models\MaintenanceManagement\PartBrand|null $primaryBrand
 * @property-read \App\Models\MaintenanceManagement\Supplier|null $primarySupplier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\PartsList> $templates
 * @property-read int|null $templates_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt1BrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt1PartCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt1PartNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt1PartSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt2BrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt2PartCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt2PartNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereAlt2PartSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereDni($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereGeneralSupplyItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereMinStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part wherePartCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part wherePartName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part wherePrimaryBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part wherePrimaryPartCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part wherePrimaryPartNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part wherePrimaryPartSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereStockLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Part whereUpdatedAt($value)
 */
	class Part extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartBrand whereUpdatedAt($value)
 */
	class PartBrand extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $used_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Part> $parts
 * @property-read int|null $parts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartCategory whereUpdatedAt($value)
 */
	class PartCategory extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property int|null $category_id
 * @property string|null $description
 * @property array<array-key, mixed>|null $selected_products
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\ProductCategory|null $category
 * @property-read \App\Models\Iam\Personnel\User|null $creator
 * @property-read mixed $product_details
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Part> $parts
 * @property-read int|null $parts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereSelectedProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartsList whereUpdatedAt($value)
 */
	class PartsList extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property int $pending_before_hours
 * @property int $pending_after_hours
 * @property int $pending_before_dates
 * @property int $pending_after_dates
 * @property string|null $master_admin_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings whereMasterAdminCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings wherePendingAfterDates($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings wherePendingAfterHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings wherePendingBeforeDates($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings wherePendingBeforeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceMasterSettings whereUpdatedAt($value)
 */
	class ServiceMasterSettings extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement\ServiceMaster{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\ServiceMaster\ServiceTask> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate> $templates
 * @property-read int|null $templates_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceCategory withoutTrashed()
 */
	class ServiceCategory extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement\ServiceMaster{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $estimated_duration
 * @property int|null $category_id
 * @property bool $auto_apply
 * @property bool $inspection_required
 * @property string|null $instructions
 * @property array<array-key, mixed>|null $reference_links
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplateTask> $templateTasks
 * @property-read int|null $template_tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereAutoApply($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereEstimatedDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereInspectionRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereReferenceLinks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTask withoutTrashed()
 */
	class ServiceTask extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement\ServiceMaster{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $preset_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\MaintenanceManagement\IntervalPreset|null $preset
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplateTask> $templateTasks
 * @property-read int|null $template_tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate wherePresetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplate withoutTrashed()
 */
	class ServiceTemplate extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement\ServiceMaster{
/**
 * @property int $id
 * @property int $template_id
 * @property int $task_id
 * @property array<array-key, mixed>|null $intervals
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MaintenanceManagement\ServiceMaster\ServiceTask|null $task
 * @property-read \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereIntervals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceTemplateTask whereUpdatedAt($value)
 */
	class ServiceTemplateTask extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $status
 * @property string|null $payment_terms
 * @property string|null $tags
 * @property string|null $primary_contact_name
 * @property string|null $primary_contact_email
 * @property string|null $primary_contact_phone
 * @property string|null $inside_sales_name
 * @property string|null $inside_sales_email
 * @property string|null $inside_sales_phone
 * @property string|null $technical_support_name
 * @property string|null $technical_support_email
 * @property string|null $technical_support_phone
 * @property string|null $billing_contact_name
 * @property string|null $billing_contact_email
 * @property string|null $billing_contact_phone
 * @property string|null $city
 * @property int|null $state_id
 * @property \App\Models\Locations\State|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $tax_id
 * @property int|null $supplier_category_id
 * @property string|null $website
 * @property string|null $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $company_logo_media_id
 * @property int $is_preferred_supplier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Part> $alt1Parts
 * @property-read int|null $alt1_parts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Part> $alt2Parts
 * @property-read int|null $alt2_parts_count
 * @property-read \App\Models\MaintenanceManagement\PartCategory|null $category
 * @property-read mixed $all_supplied_parts
 * @property-read mixed $full_address
 * @property-read mixed $logo_url
 * @property-read mixed $tag_objects
 * @property-read mixed $tags_array
 * @property-read \App\Models\Global\Media|null $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Part> $primaryParts
 * @property-read int|null $primary_parts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier search($search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereBillingContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereBillingContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereBillingContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCompanyLogoMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereInsideSalesEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereInsideSalesName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereInsideSalesPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereIsPreferredSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePrimaryContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePrimaryContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePrimaryContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereSupplierCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereTechnicalSupportEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereTechnicalSupportName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereTechnicalSupportPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereZipCode($value)
 */
	class Supplier extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $used_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Supplier> $suppliers
 * @property-read int|null $suppliers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierCategory whereUpdatedAt($value)
 */
	class SupplierCategory extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $used_by
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupplierTag whereUpdatedAt($value)
 */
	class SupplierTag extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string $category
 * @property string|null $description
 * @property bool $is_active
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $parts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Part> $parts
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Template whereUpdatedAt($value)
 */
	class Template extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property int $id
 * @property int $template_id
 * @property int $part_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart wherePartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplatePart whereUpdatedAt($value)
 */
	class TemplatePart extends \Eloquent {}
}

namespace App\Models\MaintenanceManagement{
/**
 * @property-read \App\Models\MaintenanceManagement\ServiceMaster\ServiceTask|null $task
 * @property-read \App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateTask query()
 */
	class TemplateTask extends \Eloquent {}
}

namespace App\Models\Opportunity{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $type
 * @property string $question_key
 * @property string $question_text
 * @property int $required
 * @property string $answer_type
 * @property string|null $sub_text
 * @property int $display_order
 * @property string $answer_grid
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Opportunity\OpportunityQuestionOption> $options
 * @property-read int|null $options_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereAnswerGrid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereAnswerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereQuestionKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereQuestionText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereSubText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestion whereUpdatedAt($value)
 */
	class OpportunityQuestion extends \Eloquent {}
}

namespace App\Models\Opportunity{
/**
 * @property int $id
 * @property int $opportunity_question_id
 * @property string $value
 * @property string $label
 * @property int $display_order
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Opportunity\OpportunityQuestion|null $question
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereOpportunityQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpportunityQuestionOption whereValue($value)
 */
	class OpportunityQuestionOption extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property string|null $reference_order_number
 * @property string $order_number
 * @property int|null $invoice_id
 * @property string $receipt_status
 * @property string $order_date
 * @property string|null $order_time
 * @property int|null $customer_id
 * @property string $customer_name
 * @property string|null $customer_email
 * @property string|null $customer_phone
 * @property string|null $company_name
 * @property string|null $company_website
 * @property numeric $subtotal
 * @property string $is_tax_exempt
 * @property numeric $tax_amount
 * @property string|null $coupon_code
 * @property numeric $discount_amount
 * @property numeric $grand_total
 * @property string|null $order_note
 * @property array<array-key, mixed>|null $cart_data
 * @property array<array-key, mixed>|null $terms_collection
 * @property string|null $pending_terms_content
 * @property string|null $accepted_terms_content
 * @property string|null $terms_accepted_at
 * @property \App\Enums\Orders\OrderTermsStatus $terms_status
 * @property string|null $last_terms_sms_sent_at
 * @property string|null $signature_image
 * @property string $platform
 * @property string|null $created_by_type
 * @property int|null $created_by_id
 * @property string|null $updated_by_type
 * @property int|null $updated_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $po_id
 * @property bool $auto_inject
 * @property int|null $auto_inject_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderAddress> $addresses
 * @property-read int|null $addresses_count
 * @property-read \App\Models\Orders\OrderAddress|null $billingAddress
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $createdBy
 * @property-read \App\Models\Customers\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderMedia> $deliveryMedia
 * @property-read int|null $delivery_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderExtraCharges> $extraCharges
 * @property-read int|null $extra_charges_count
 * @property-read mixed $is_paid
 * @property-read mixed $last_payment_status
 * @property-read mixed $last_payment_type
 * @property-read mixed $remaining_amount
 * @property-read mixed $total_refunded
 * @property-read mixed $view_link
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderHistory> $history
 * @property-read int|null $history_count
 * @property-read \App\Models\Customers\Invoice|null $invoice
 * @property-read \App\Models\Orders\OrderPayment|null $lastPaidPayment
 * @property-read \App\Models\Orders\OrderPayment|null $lastPayment
 * @property-read \App\Models\Orders\OrderPayment|null $lastRefundPayment
 * @property-read \App\Models\Customers\Receipt|null $latestReceipt
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderMedia> $licenseMedia
 * @property-read int|null $license_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderMedia> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderNote> $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderMedia> $pickupMedia
 * @property-read int|null $pickup_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProduct> $products
 * @property-read int|null $products_count
 * @property-read Order|null $referenceOrder
 * @property-read \App\Models\Orders\OrderAddress|null $shippingAddress
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAcceptedTermsContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAutoInject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAutoInjectBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCartData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCompanyWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCouponCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedByType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereIsTaxExempt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereLastTermsSmsSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePendingTermsContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereReceiptStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereReferenceOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSignatureImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTermsAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTermsCollection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTermsStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedByType($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property int $order_id
 * @property string $type
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string $address
 * @property string $city
 * @property string|null $state
 * @property int|null $state_id
 * @property string|null $zip_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $full_address
 * @property-read mixed $full_name
 * @property-read \App\Models\Orders\Order $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderAddress whereZipCode($value)
 */
	class OrderAddress extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $order_id
 * @property int|null $order_product_id
 * @property int $customer_id
 * @property numeric $amount
 * @property string $type
 * @property string|null $payment_type
 * @property string|null $payment_number_id
 * @property string|null $auth_code
 * @property string|null $customer_profile_id
 * @property string|null $payment_profile_id
 * @property int|null $responsible_person_id
 * @property string|null $responsible_person_name
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\Customer $customer
 * @property-read \App\Models\Orders\Order $order
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @property-read \App\Models\Iam\Personnel\User|null $responsiblePerson
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges damage()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges fuel()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereAuthCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges wherePaymentNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges wherePaymentProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereResponsiblePersonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereResponsiblePersonName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderExtraCharges whereUpdatedAt($value)
 */
	class OrderExtraCharges extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $order_id
 * @property int|null $customer_id
 * @property int|null $user_id
 * @property \App\Enums\Orders\OrderHistoryActionBy|null $action_by
 * @property string $action_date
 * @property \App\Enums\Orders\OrderHistoryAction $action
 * @property string|null $description
 * @property string|null $extras
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customers\Customer|null $customer
 * @property-read \App\Models\Orders\Order|null $order
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereActionBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereActionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereExtras($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderHistory whereUserId($value)
 */
	class OrderHistory extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property \App\Enums\Orders\OrderMediaType $type
 * @property string|null $side
 * @property int $order_id
 * @property int|null $order_product_id
 * @property int|null $media_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Iam\Personnel\User|null $createdBy
 * @property-read \App\Models\Global\Media|null $media
 * @property-read \App\Models\Orders\Order $order
 * @property-read \App\Models\Orders\OrderProduct|null $orderProduct
 * @property-read \App\Models\Iam\Personnel\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereSide($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderMedia whereUpdatedBy($value)
 */
	class OrderMedia extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $order_id
 * @property string $note
 * @property string $note_type
 * @property int|null $user_id
 * @property string|null $created_by_type
 * @property int|null $created_by_id
 * @property string|null $updated_by_type
 * @property int|null $updated_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $createdBy
 * @property-read mixed $created_by_type_name
 * @property-read mixed $updated_by_type_name
 * @property-read \App\Models\Orders\Order $order
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $updatedBy
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote dashboard()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereCreatedByType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereNoteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUpdatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUpdatedByType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUserId($value)
 */
	class OrderNote extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property int|null $parent_order_payment_id
 * @property string $unique_id
 * @property int $order_id
 * @property \App\Enums\Orders\OrderPaymentMethod|null $payment_method
 * @property string|null $payment_datetime
 * @property string|null $transaction_id
 * @property string|null $card_number
 * @property string|null $card_first_name
 * @property string|null $card_last_name
 * @property string|null $auth_code
 * @property string|null $customer_profile_id
 * @property string|null $payment_profile_id
 * @property string|null $cheque_number
 * @property string|null $payment_note
 * @property numeric $amount
 * @property \App\Enums\Orders\OrderPaymentStatus|null $status
 * @property numeric $refund_amount
 * @property string|null $refund_note
 * @property array<array-key, mixed>|null $payment_response
 * @property string|null $created_by_type
 * @property int|null $created_by_id
 * @property string|null $updated_by_type
 * @property int|null $updated_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment cod()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment paid()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment refund()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereAuthCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCardFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCardLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereChequeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCreatedByType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereParentOrderPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereRefundAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereRefundNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereUpdatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereUpdatedByType($value)
 */
	class OrderPayment extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property numeric $price
 * @property int $quantity
 * @property string $hour_tracking
 * @property numeric $hour_rate
 * @property numeric $allocated_hours
 * @property numeric $sub_total
 * @property numeric $tax
 * @property numeric $total
 * @property array<array-key, mixed>|null $product_data
 * @property string|null $service_method
 * @property string|null $service_option
 * @property string|null $distance_type
 * @property string|null $distance_range
 * @property string $delivery_status
 * @property string|null $delivery_transport_mode
 * @property int|null $delivery_store_id
 * @property string|null $delivery_date
 * @property string|null $delivery_time
 * @property int|null $delivery_by
 * @property int|null $delivery_signature_media_id
 * @property string|null $delivery_notes
 * @property int $is_delivered
 * @property string $pickup_status
 * @property string|null $pickup_transport_mode
 * @property int|null $pickup_store_id
 * @property string|null $pickup_date
 * @property string|null $pickup_time
 * @property int|null $pickup_by
 * @property int|null $pickup_signature_media_id
 * @property string|null $pickup_notes
 * @property int $is_returned
 * @property string|null $start_hours
 * @property string|null $end_hours
 * @property string|null $fuel_initial_reading
 * @property string|null $fuel_final_reading
 * @property string|null $fuel_total_charge
 * @property string $fuel_charge_status
 * @property string|null $total_charge
 * @property numeric $damage_charge
 * @property string $damage_status
 * @property int|null $equipment_id
 * @property array<array-key, mixed>|null $equipment_details
 * @property int|null $assigned_by
 * @property string|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProductChecklistQuestion> $checklistQuestions
 * @property-read int|null $checklist_questions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProductDamageChargeLog> $damageChargeLogs
 * @property-read int|null $damage_charge_logs_count
 * @property-read \App\Models\Iam\Personnel\User|null $deliveryEmployee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderMedia> $deliveryMedia
 * @property-read int|null $delivery_media_count
 * @property-read \App\Models\Global\Media|null $deliverySignatureMedia
 * @property-read \App\Models\Stores\Store|null $deliveryStore
 * @property-read \App\Models\MaintenanceManagement\Equipment|null $equipment
 * @property-read \App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate|null $equipmentRentalReadyTemplate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProductFuelChargeLog> $fuelChargeLogs
 * @property-read int|null $fuel_charge_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProductFunnelLog> $funnelLogs
 * @property-read int|null $funnel_logs_count
 * @property-read float $current_damage_charge
 * @property-read float $current_fuel_charge
 * @property-read \App\Models\Orders\Order $order
 * @property-read \App\Models\Iam\Personnel\User|null $pickupEmployee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderMedia> $pickupMedia
 * @property-read int|null $pickup_media_count
 * @property-read \App\Models\Stores\Store|null $pickupStore
 * @property-read \App\Models\ProductManagement\Product|null $product
 * @property-read \App\Models\Global\Media|null $returnSignatureMedia
 * @property-read \App\Models\MaintenanceManagement\EquipmentSoftAssign|null $softAssignment
 * @property-read \App\Models\MaintenanceManagement\Equipment|null $softEquipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereAllocatedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereAssignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDamageCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDamageStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliverySignatureMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDeliveryTransportMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDistanceRange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereDistanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereEndHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereEquipmentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereEquipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereFuelChargeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereFuelFinalReading($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereFuelInitialReading($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereFuelTotalCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereHourRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereHourTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereIsDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereIsReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupSignatureMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePickupTransportMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereProductData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereServiceMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereServiceOption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereStartHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereSubTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereTotalCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProduct whereUpdatedAt($value)
 */
	class OrderProduct extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $order_id
 * @property int $order_product_id
 * @property int|null $question_id
 * @property int|null $question_category_id
 * @property string|null $question_name
 * @property string|null $delivery_question
 * @property string|null $return_question
 * @property int $index_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Orders\OrderProductChecklistQuestionAnswers> $answers
 * @property-read int|null $answers_count
 * @property-read \App\Models\Orders\OrderProductChecklistQuestionAnswers|null $deliverySelectedAnswer
 * @property-read mixed $effective_latest_answer
 * @property-read \App\Models\Orders\OrderProductChecklistQuestionAnswers|null $latestAnswer
 * @property-read \App\Models\Orders\OrderProductChecklistQuestionAnswers|null $latestValidAnswer
 * @property-read \App\Models\Orders\OrderProduct $orderProduct
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion|null $question
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory|null $questionCategory
 * @property-read \App\Models\Orders\OrderProductChecklistQuestionAnswers|null $returnSelectedAnswer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion indexOrder()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereDeliveryQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereQuestionCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereQuestionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereReturnQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestion whereUpdatedAt($value)
 */
	class OrderProductChecklistQuestion extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $order_id
 * @property int $order_product_checklist_question_id
 * @property int|null $question_id
 * @property int|null $answer_id
 * @property string|null $delivery_answer
 * @property string|null $return_answer
 * @property numeric|null $delivery_amount
 * @property numeric|null $return_amount
 * @property numeric|null $user_delivery_amount
 * @property numeric|null $user_return_amount
 * @property int $is_delivery_answer
 * @property int $is_return_answer
 * @property int $is_sync
 * @property int $index_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer|null $answer
 * @property-read \App\Models\Orders\OrderProductChecklistQuestion $orderProductChecklistQuestion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereAnswerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereDeliveryAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereDeliveryAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereIsDeliveryAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereIsReturnAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereIsSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereOrderProductChecklistQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereReturnAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereReturnAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereUserDeliveryAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductChecklistQuestionAnswers whereUserReturnAmount($value)
 */
	class OrderProductChecklistQuestionAnswers extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property int $order_product_id
 * @property numeric $before_amount
 * @property numeric $change_amount
 * @property numeric $after_amount
 * @property string|null $action
 * @property int|null $user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Orders\OrderProduct $orderProduct
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereAfterAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereBeforeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereChangeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductDamageChargeLog whereUserId($value)
 */
	class OrderProductDamageChargeLog extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property int $order_product_id
 * @property numeric $before_amount
 * @property numeric $change_amount
 * @property numeric $after_amount
 * @property string|null $action
 * @property int|null $user_id
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Orders\OrderProduct $orderProduct
 * @property-read \App\Models\Iam\Personnel\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereAfterAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereBeforeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereChangeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFuelChargeLog whereUserId($value)
 */
	class OrderProductFuelChargeLog extends \Eloquent {}
}

namespace App\Models\Orders{
/**
 * @property int $id
 * @property int $order_product_id
 * @property int $sales_funnel_id
 * @property int|null $product_id
 * @property int|null $sales_funnel_step_id
 * @property string|null $step_type
 * @property string|null $step_name
 * @property string|null $message
 * @property string $status
 * @property string|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Orders\OrderProduct $orderProduct
 * @property-read \App\Models\Customers\SalesFunnel $salesFunnel
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereSalesFunnelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereSalesFunnelStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereStepName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereStepType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderProductFunnelLog whereUpdatedAt($value)
 */
	class OrderProductFunnelLog extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $product_name
 * @property string $slug
 * @property string $product_type
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $short_description
 * @property string|null $description
 * @property int|null $media_id
 * @property int $is_general_term_type
 * @property int $is_custom_term_type
 * @property string|null $sku
 * @property string|null $barcode
 * @property numeric|null $retail_price
 * @property numeric|null $retail_sale_price
 * @property numeric|null $retail_product_cost
 * @property numeric|null $rental_daily
 * @property numeric|null $rental_weekend
 * @property numeric|null $rental_weekly
 * @property numeric|null $rental_monthly
 * @property numeric|null $rental_damage_waiver_daily
 * @property numeric|null $rental_damage_waiver_weekend
 * @property numeric|null $rental_damage_waiver_weekly
 * @property numeric|null $rental_damage_waiver_monthly
 * @property numeric|null $rental_track_insurance_daily
 * @property numeric|null $rental_track_insurance_weekend
 * @property numeric|null $rental_track_insurance_weekly
 * @property numeric|null $rental_track_insurance_monthly
 * @property numeric|null $rental_tire_insurance_daily
 * @property numeric|null $rental_tire_insurance_weekend
 * @property numeric|null $rental_tire_insurance_weekly
 * @property numeric|null $rental_tire_insurance_monthly
 * @property numeric|null $rental_prepaid_cleaning
 * @property numeric|null $rental_prepaid_fuel
 * @property numeric|null $sale_price_daily
 * @property numeric|null $sale_price_weekend
 * @property numeric|null $sale_price_weekly
 * @property numeric|null $sale_price_monthly
 * @property numeric|null $related_product_price_daily
 * @property numeric|null $related_product_price_weekend
 * @property numeric|null $related_product_price_weekly
 * @property numeric|null $related_product_price_monthly
 * @property numeric|null $standard_delivery_fee
 * @property numeric|null $extended_delivery_fee
 * @property string|null $in_store_pickup
 * @property string|null $delivery_and_pickup
 * @property string|null $hour_tracking
 * @property numeric|null $hour_rate
 * @property bool $is_default_funnel
 * @property bool $has_high_demand_alert
 * @property string|null $truck_fee_size_setting
 * @property string|null $track_insurance_size_setting
 * @property string|null $tire_insurance_size_setting
 * @property string|null $prepaid_cleaning_rate_setting
 * @property string|null $prepaid_fuel_rate_setting
 * @property bool $is_tax_free_item
 * @property bool $apply_special_tax
 * @property bool $apply_added_fees
 * @property string $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\ProductCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customers\SalesFunnel> $funnels
 * @property-read int|null $funnels_count
 * @property-read mixed $hover_image_url
 * @property-read mixed $image_url
 * @property-read mixed $is_on_sale
 * @property-read \App\Models\Global\Media|null $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\ProductMediaChild> $mediaChildren
 * @property-read int|null $media_children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\ProductOption> $options
 * @property-read int|null $options_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $relatedProducts
 * @property-read int|null $related_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TermsAndConditions\Terms> $terms
 * @property-read int|null $terms_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product filterByPriceType($priceType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereApplyAddedFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereApplySpecialTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeliveryAndPickup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereExtendedDeliveryFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHasHighDemandAlert($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHourRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereHourTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereInStorePickup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsCustomTermType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsDefaultFunnel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsGeneralTermType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsTaxFreeItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrepaidCleaningRateSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrepaidFuelRateSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereProductType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRelatedProductPriceDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRelatedProductPriceMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRelatedProductPriceWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRelatedProductPriceWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalDamageWaiverDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalDamageWaiverMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalDamageWaiverWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalDamageWaiverWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalPrepaidCleaning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalPrepaidFuel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTireInsuranceDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTireInsuranceMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTireInsuranceWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTireInsuranceWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTrackInsuranceDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTrackInsuranceMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTrackInsuranceWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalTrackInsuranceWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRentalWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRetailPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRetailProductCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereRetailSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSalePriceDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSalePriceMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSalePriceWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSalePriceWeekly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSeoDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSeoTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereShortDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStandardDeliveryFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTireInsuranceSizeSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTrackInsuranceSizeSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTruckFeeSizeSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 */
	class Product extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $parent_id
 * @property int|null $schedule_assignment_category_id
 * @property string $title
 * @property string $slug
 * @property string|null $short_content
 * @property string|null $content
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int|null $media_id
 * @property int|null $hover_media_id
 * @property string $status
 * @property string $is_featured
 * @property int $sort_order
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\ProductCategoryChild> $categoryChildren
 * @property-read int|null $category_children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductCategory> $childCategories
 * @property-read int|null $child_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceManagement\Equipment> $equipments
 * @property-read int|null $equipments_count
 * @property-read mixed $hover_image_url
 * @property-read mixed $image_url
 * @property-read \App\Models\Global\Media|null $hoverMedia
 * @property-read \App\Models\Global\Media|null $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductCategory> $pageCategoriesBySortOrder
 * @property-read int|null $page_categories_by_sort_order_count
 * @property-read ProductCategory|null $parentCategory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductCategory> $schedulesCategories
 * @property-read int|null $schedules_categories_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory orderByAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory parent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory sortOrder()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereHoverMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereScheduleAssignmentCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereSeoDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereSeoTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereShortContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 */
	class ProductCategory extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property int|null $product_id
 * @property int $product_category_id
 * @property int|null $sub_category_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\Product|null $product
 * @property-read \App\Models\ProductManagement\ProductCategory $productCategory
 * @property-read \App\Models\ProductManagement\ProductCategory|null $subCategory
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild sortOrder()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereSubCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategoryChild whereUpdatedAt($value)
 */
	class ProductCategoryChild extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property int $product_id
 * @property int $media_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Global\Media $media
 * @property-read \App\Models\ProductManagement\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild sortOrder($direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMediaChild whereUpdatedAt($value)
 */
	class ProductMediaChild extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string $type
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductManagement\ProductOptionItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption inactive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereUpdatedAt($value)
 */
	class ProductOption extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property int $product_id
 * @property int $product_option_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\Product $product
 * @property-read \App\Models\ProductManagement\ProductOption $productOption
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild whereProductOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionChild whereUpdatedAt($value)
 */
	class ProductOptionChild extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $product_option_id
 * @property string $label
 * @property numeric|null $daily
 * @property numeric|null $weekend
 * @property numeric|null $weekly
 * @property numeric|null $monthly
 * @property numeric|null $retail_price
 * @property string $charged
 * @property string $value
 * @property string|null $comment
 * @property string|null $accept_label
 * @property string|null $decline_label
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\ProductOption $productOption
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereAcceptLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereCharged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereDaily($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereDeclineLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereProductOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereRetailPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionItem whereWeekly($value)
 */
	class ProductOptionItem extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property int $product_id
 * @property int $related_product_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\Product $product
 * @property-read \App\Models\ProductManagement\Product $relatedProduct
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild whereRelatedProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRelatedProductChild whereUpdatedAt($value)
 */
	class ProductRelatedProductChild extends \Eloquent {}
}

namespace App\Models\ProductManagement{
/**
 * @property int $id
 * @property int $product_id
 * @property int $terms_and_condition_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductManagement\Product $product
 * @property-read \App\Models\TermsAndConditions\Terms|null $terms
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild whereTermsAndConditionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductTermsChild whereUpdatedAt($value)
 */
	class ProductTermsChild extends \Eloquent {}
}

namespace App\Models\Stores{
/**
 * @property int $id
 * @property string $unique_id
 * @property int $status
 * @property int|null $store_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property array<array-key, mixed>|null $personal_details
 * @property array<array-key, mixed>|null $job_preferences
 * @property array<array-key, mixed>|null $experience_details
 * @property array<array-key, mixed>|null $skills
 * @property array<array-key, mixed>|null $driving_details
 * @property int|null $resume_media_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Global\Media|null $media
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereDrivingDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereExperienceDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereJobPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application wherePersonalDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereResumeMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUpdatedAt($value)
 */
	class Application extends \Eloquent {}
}

namespace App\Models\Stores{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $title
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmploymentPosition whereUpdatedAt($value)
 */
	class EmploymentPosition extends \Eloquent {}
}

namespace App\Models\Stores{
/**
 * @property int $id
 * @property string $unique_id
 * @property int|null $store_id
 * @property string $day_name
 * @property int $is_closed
 * @property string|null $start_time
 * @property string|null $end_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Stores\Store|null $store
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereDayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereIsClosed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HoursOfOperation whereUpdatedAt($value)
 */
	class HoursOfOperation extends \Eloquent {}
}

namespace App\Models\Stores{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $store_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string $country
 * @property int|null $state_id
 * @property string|null $city
 * @property string|null $zip_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $details
 * @property string $is_primary
 * @property string $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $lunch_start_time
 * @property-read mixed $full_address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stores\HoursOfOperation> $hours
 * @property-read int|null $hours_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stores\HoursOfOperation> $hoursOfOperation
 * @property-read int|null $hours_of_operation_count
 * @property-read \App\Models\Locations\State|null $state
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Iam\Personnel\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store orderByAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store primary()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereLunchStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereStoreName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Store whereZipCode($value)
 */
	class Store extends \Eloquent {}
}

namespace App\Models\TermsAndConditions{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string|null $signature_block
 * @property string $is_global
 * @property string $status
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms global()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms order()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms orderByTitle()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms product()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereIsGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereSeoDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereSeoTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereSignatureBlock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Terms withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 */
	class Terms extends \Eloquent {}
}

namespace App\Models\WebsiteManagement\FaqPage{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $category_name
 * @property string|null $description
 * @property bool $default_expand
 * @property int|null $category_icon_media_id
 * @property int|null $category_index_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Global\Media|null $iconMedia
 * @property-read \App\Models\Global\Media|null $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WebsiteManagement\FaqPage\FaqQuestions> $questions
 * @property-read int|null $questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereCategoryIconMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereCategoryIndexNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereDefaultExpand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqCategory whereUpdatedAt($value)
 */
	class FaqCategory extends \Eloquent {}
}

namespace App\Models\WebsiteManagement\FaqPage{
/**
 * @property int $id
 * @property string $unique_id
 * @property string $question_name
 * @property int $category_id
 * @property string|null $answer
 * @property string $status
 * @property int|null $related_question_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WebsiteManagement\FaqPage\FaqCategory $category
 * @property-read FaqQuestions|null $related
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereQuestionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereRelatedQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaqQuestions whereUpdatedAt($value)
 */
	class FaqQuestions extends \Eloquent {}
}


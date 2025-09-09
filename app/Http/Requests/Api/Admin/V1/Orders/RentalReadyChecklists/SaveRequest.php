<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\RentalReadyChecklists;

use App\Http\Requests\ApiBaseFormRequest;

class SaveRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_product_unique_id' => 'required|string|exists:order_products,unique_id',
            'user_id' => 'required|string|exists:users,id',
            'equipment_hours' => 'required|string',
            'general_notes' => 'nullable|string',
            'checklist' => 'required|array',
            'checklist.*.question_unique_id' => 'required',
            'checklist.*.answer_id' => 'required',
            'checklist.*.note' => 'nullable|string',
        ];
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'order_product_unique_id' => [
                'description' => 'The unique_id of an existing record in the order_products table.',
                'example' => 'ORD-SCH-DFVD-X6GT',
            ],
            'user_id' => [
                'description' => 'The ID of an existing user in the users table.',
                'example' => 1,
            ],
            'equipment_hours' => [
                'description' => 'The equipment hours at the time of inspection.',
                'example' => '150',
            ],
            'general_notes' => [
                'description' => 'Any general notes regarding the rental ready checklist.',
                'example' => 'The equipment is in good condition.',
            ],
            'checklist[]' => [
                'description' => 'An array of checklist items with question unique IDs and answers.',
                'example' => [
                    [
                        'question_unique_id' => 'QST-V9ZN-Z77W',
                        'answer_id' => '7',
                        'note' => 'The equipment is in good condition.',
                    ],
                ],
            ],

            // // Crucial: examples for nested fields
            'checklist.*.question_unique_id' => [
                'description' => 'The unique_id of an existing record in the customer_admin_questions table.',
                'example' => 'QST-V9ZN-Z77W',
            ],
            'checklist.*.answer_id' => [
                'description' => 'The unique_id of an existing record in the customer_admin_question_answers table.',
                'example' => '7',
            ],
            'checklist.*.note' => [
                'description' => 'Optional note.',
                'example' => 'The equipment is in good condition.',
            ],
        ];
    }
}

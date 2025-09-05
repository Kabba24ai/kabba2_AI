<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists;

use App\Http\Requests\ApiBaseFormRequest;

class SaveDeliveryRequest extends ApiBaseFormRequest
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
            'equipment_unique_id' => 'required|string|exists:equipment,unique_id',
            'note' => 'nullable|string',
            //'signature_media' => 'nullable|image|max:2048', // Max 2MB
            'user_id' => 'required|string|exists:users,id',
            'checklist' => 'required|array',
            'checklist.*.question_unique_id' => 'required|exists:customer_admin_questions,unique_id',
            'checklist.*.answer_unique_id' => 'required|exists:customer_admin_question_answers,unique_id',
            'checklist.*.amount' => 'nullable|string',
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
                'description' => 'The unique identifier of the order product.',
                'example' => 'ORD-SCH-OTQ0-OBSO',
            ],
            'equipment_unique_id' => [
                'description' => 'The unique identifier of the equipment.',
                'example' => 'EQP-OBGV-UMHR',
            ],
            'note' => [
                'description' => 'An optional note related to the checklist.',
                'example' => 'Customer requested special handling.',
            ],
            // 'signature_media' => [
            //     'description' => 'Customer signature image (PNG/JPG). Max 2MB.',
            //     'type' => 'file',
            // ],
            'user_id' => [
                'description' => 'The ID of the user saving the checklist.',
                'example' => '1',
            ],

            // The array itself (optional but nice to show a full sample)
            'checklist[]' => [
                'description' => 'Array of checklist items.',
                'example' => [
                    [
                        'question_unique_id' => 'CAQST-ENWR-V9FJ',
                        'answer_unique_id' => 'CAANS-PTCJ-NDIB',
                        'amount' => '5.00',
                    ],
                ],
            ],

            // Crucial: examples for nested fields
            'checklist.*.question_unique_id' => [
                'description' => 'The unique_id of an existing record in the customer_admin_questions table.',
                'example' => 'CAQST-ENWR-V9FJ',
            ],
            'checklist.*.answer_unique_id' => [
                'description' => 'The unique_id of an existing record in the customer_admin_question_answers table.',
                'example' => 'CAANS-PTCJ-NDIB',
            ],
            'checklist.*.amount' => [
                'description' => 'Optional amount.',
                'example' => '5.00',
            ],
        ];
    }
}

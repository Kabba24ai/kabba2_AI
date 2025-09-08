<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class SaveReturnRequest extends ApiBaseFormRequest
{
    protected function prepareForValidation()
    {
        \Log::warning(json_encode($this->all(),true ,JSON_PRETTY_PRINT));
        $input = $this->all();
        // Build checklist[] from keys like checklist_0_question_unique_id
        // $checklist = [];
        // foreach ($input as $key => $value) {
        //     if (preg_match('/^checklist_(\d+)_(question_unique_id|answer_unique_id|amount)$/', $key, $m)) {
        //         $idx   = (int) $m[1];
        //         $field = $m[2];
        //         $checklist[$idx][$field] = $value;
        //         unset($input[$key]);
        //     }
        // }

        // if (!empty($checklist)) {
        //     // Ensure numeric indices in order
        //     ksort($checklist);
        //     $input['checklist'] = array_values($checklist);
        // }

        // // Purify only non-file inputs
        // $clean = PurifyHelper::purify($input);

        // // Replace request payload
        // $this->replace($clean);

        \Log::debug('SaveReturnRequest::prepareForValidation', $this->all());
        \Log::debug('SaveReturnRequest::prepareForValidation - checklist', $this->input('checklist') ?? []);
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_product_unique_id' => 'required|string|exists:order_products,unique_id',
            'store_id' => 'required|string|exists:stores,id',
            'user_id' => 'required|string|exists:users,id',
            'end_hours' => 'nullable|string',
            'total_charge' => 'nullable|string',
            'note' => 'nullable|string',

             // For multipart, Laravel's "max" is in KB; 2048 = 2MB
            'signature_media'         => 'nullable|image|max:2048',

            'checklist'             => 'required|array',
            'checklist.*.question_unique_id' => 'required|string|exists:order_product_checklist_questions,unique_id',
            'checklist.*.answer_unique_id'   => 'required|string|exists:order_product_checklist_question_answers,unique_id',
            'checklist.*.amount'             => 'nullable|string',
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
            'store_id' => [
                'description' => 'The ID of the store where the checklist is being saved.',
                'example' => '1',
            ],
            'user_id' => [
                'description' => 'The ID of the user saving the checklist.',
                'example' => '1',
            ],
            'end_hours' => [
                'description' => 'The start hours for the delivery.',
                'example' => '14',
            ],
            'total_charge' => [
                'description' => 'Total charge for the return.',
                'example' => '150.00',
            ],
            'note' => [
                'description' => 'An optional note related to the checklist.',
                'example' => 'Customer requested special handling.',
            ],
            'signature_media' => [
                'description' => 'Customer signature image (PNG/JPG). Max 2MB.',
                'type' => 'file',
            ],
            'checklist[]' => [
                'description' => 'Array of checklist items.',
                'example' => [
                    [
                        'question_unique_id' => 'ORD-QUE-CDVS-ZHWB',
                        'answer_unique_id' => 'ORD-ANS-MKFA-7VJU',
                        'amount' => '5.00',
                    ],
                ],
            ],

            // Crucial: examples for nested fields
            'checklist.*.question_unique_id' => [
                'description' => 'The unique_id of an existing record in the customer_admin_questions table.',
                'example' => 'ORD-QUE-CDVS-ZHWB',
            ],
            'checklist.*.answer_unique_id' => [
                'description' => 'The unique_id of an existing record in the customer_admin_question_answers table.',
                'example' => 'ORD-ANS-MKFA-7VJU',
            ],
            'checklist.*.amount' => [
                'description' => 'Optional amount.',
                'example' => '5.00',
            ],
        ];
    }
}

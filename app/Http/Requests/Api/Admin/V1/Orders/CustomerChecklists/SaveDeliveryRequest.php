<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists;

use App\Http\Requests\ApiBaseFormRequest;

class SaveDeliveryRequest extends ApiBaseFormRequest
{
    protected function prepareForValidation()
    {

        $input = $this->all();

        if (isset($input['checklist'])) {
            $raw = $input['checklist'];

            // Case: ["<json>"]
            if (is_array($raw) && count($raw) === 1 && is_string($raw[0])) {
                $raw = $raw[0];
            }

            // Case: string JSON or weird iOS string
            if (is_string($raw)) {
                // Try decode directly
                $decoded = json_decode($raw, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Try to fix weird iOS format: "\"question_unique_id\": \"...\", \"answer_unique_id\": \"...\""
                    $candidate = trim($raw, "[]\""); // strip [ ] and quotes
                    $candidate = '{' . $candidate . '}'; // wrap into object
                    $decoded   = json_decode($candidate, true);
                }

                // Wrap into array if single object
                if (is_array($decoded) && !array_is_list($decoded)) {
                    $decoded = [$decoded];
                }

                $input['checklist'] = $decoded;
            }
        }

        $this->replace($input);

        // $input = $this->all();
        // // Build checklist[] from keys like checklist_0_question_unique_id
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
            'equipment_unique_id' => 'required|string|exists:equipment,unique_id',
            'store_id' => 'nullable|string|exists:stores,id',
            'user_id' => 'required|string|exists:users,id',
            'start_hours' => 'nullable|string',
            'fuel_initial_reading' => 'nullable|string',
            'note' => 'nullable|string',

             // For multipart, Laravel's "max" is in KB; 2048 = 2MB
            'signature_media'         => 'nullable|image|max:2048',

            'checklist'             => 'required|array',
            'checklist.*.question_unique_id' => 'required|string|exists:customer_admin_questions,unique_id',
            'checklist.*.answer_unique_id'   => 'required|string|exists:customer_admin_question_answers,unique_id',
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
            'start_hours' => [
                'description' => 'The start hours for the delivery.',
                'example' => '14',
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

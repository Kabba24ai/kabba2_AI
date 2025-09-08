<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\CustomerChecklists;

use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class SaveReturnRequest extends ApiBaseFormRequest
{
    protected function prepareForValidation()
    {
        // Start with all inputs
        $input = $this->all();

        /**
         * 1) Build checklist[] from keys like:
         *    - checklist_0_question_unique_id
         *    - checklist_0_answer_unique_id
         *    - checklist_0_amount
         */
        $checklistFromKeys = [];
        foreach ($input as $key => $value) {
            if (preg_match('/^checklist_(\d+)_(question_unique_id|answer_unique_id|amount)$/', $key, $m)) {
                $idx = (int) $m[1];
                $field = $m[2];
                $checklistFromKeys[$idx][$field] = $value;
                unset($input[$key]); // remove the flattened key
            }
        }

        if (!empty($checklistFromKeys)) {
            ksort($checklistFromKeys); // ensure numeric order
            $input['checklist'] = array_values($checklistFromKeys);
        }

        /**
         * 2) Handle incoming `checklist` that is a JSON string OR ["<json>"].
         *    After this step, $input['checklist'] will be a proper array of objects.
         */
        if (isset($input['checklist'])) {
            // Case: checklist is a single-element array with JSON string inside
            if (is_array($input['checklist']) && count($input['checklist']) === 1 && is_string($input['checklist'][0])) {
                $decoded = json_decode($input['checklist'][0], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input['checklist'] = $decoded;
                }
            }
            // Case: checklist is a raw JSON string
            elseif (is_string($input['checklist'])) {
                $decoded = json_decode($input['checklist'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input['checklist'] = $decoded;
                }
            }
            // Case: checklist is an array of JSON strings (rare, but normalize)
            elseif (is_array($input['checklist'])) {
                $first = reset($input['checklist']);
                if (is_string($first) && str_starts_with(trim($first), '[')) {
                    $decoded = json_decode($first, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $input['checklist'] = $decoded;
                    }
                }
            }

            // 2b) Normalize shape & cast amount (if present)
            if (is_array($input['checklist'])) {
                $input['checklist'] = array_values(
                    array_map(function ($row) {
                        // keep only known keys
                        $normalized = [
                            'question_unique_id' => $row['question_unique_id'] ?? null,
                            'answer_unique_id' => $row['answer_unique_id'] ?? null,
                        ];
                        if (array_key_exists('amount', $row)) {
                            $normalized['amount'] = is_null($row['amount']) || $row['amount'] === '' ? null : (float) $row['amount'];
                        }
                        return $normalized;
                    }, $input['checklist']),
                );
            }
        }

        /**
         * 3) Light casting for common numeric fields (only if present)
         */
        $ints = ['user_id', 'store_id'];
        $floats = ['start_hours', 'total_charge'];
        foreach ($ints as $f) {
            if (isset($input[$f]) && $input[$f] !== '' && $input[$f] !== null) {
                $input[$f] = (int) $input[$f];
            }
        }
        foreach ($floats as $f) {
            if (isset($input[$f]) && $input[$f] !== '' && $input[$f] !== null) {
                $input[$f] = (float) $input[$f];
            }
        }

        /**
         * 4) Purify only non-file inputs
         */
        $clean = PurifyHelper::purify($input);

        /**
         * 5) Replace request payload
         */
        $this->replace($clean);

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
            'signature_media' => 'nullable|image|max:2048',

            'checklist' => 'required|array',
            'checklist.*.question_unique_id' => 'required|string|exists:order_product_checklist_questions,unique_id',
            'checklist.*.answer_unique_id' => 'required|string|exists:order_product_checklist_question_answers,unique_id',
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

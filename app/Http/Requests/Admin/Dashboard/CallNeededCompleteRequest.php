<?php

namespace App\Http\Requests\Admin\Dashboard;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class CallNeededCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(
            PurifyHelper::purify($this->all(), [
                'completion_note',
            ])
        );
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'in:save,complete',
            ],

            'call_status' => [
                'required',
                'string',
            ],

            'completion_note' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Please select an action.',
            'action.in' => 'Invalid action selected.',

            'call_status.required' => 'Please select a call status.',

            'completion_note.required' => 'Please enter call notes.',
            'completion_note.max' => 'Call notes may not exceed 5000 characters.',
        ];
    }
}
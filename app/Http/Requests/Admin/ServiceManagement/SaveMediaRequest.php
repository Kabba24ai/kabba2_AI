<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use App\Enums\Service\ServiceMediaCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file', 'max:51200', // 50 MB
                'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm,pdf,doc,docx,xls,xlsx,txt',
            ],
            'category' => ['required', Rule::enum(ServiceMediaCategory::class)],
            'notes'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}

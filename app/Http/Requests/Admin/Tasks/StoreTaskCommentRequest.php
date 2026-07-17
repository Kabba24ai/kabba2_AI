<?php

namespace App\Http\Requests\Admin\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
            'media'   => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm'],
        ];
    }
}

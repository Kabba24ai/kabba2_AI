<?php

namespace App\Http\Requests\Admin\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'            => ['required', 'in:sales,yard,shop,admin'],
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'priority'            => ['required', 'in:low,normal,high,urgent'],
            'status'              => ['required', 'in:open,in_progress,waiting,completed,cancelled'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'due_date'            => ['nullable', 'date'],
            'related_order_id'      => ['nullable', 'integer'],
            'related_customer_id'   => ['nullable', 'integer'],
            'related_equipment_id'  => ['nullable', 'exists:equipment,id'],
        ];
    }
}

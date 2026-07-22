<?php

namespace App\Http\Requests\Admin\Tasks;

use App\Http\Requests\Admin\Tasks\Concerns\ResolvesRelatedOrderCustomer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    use ResolvesRelatedOrderCustomer;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'            => ['sometimes', 'required', 'in:sales,yard,shop,admin'],
            'title'               => ['sometimes', 'required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'priority'            => ['sometimes', 'required', 'in:low,normal,high,urgent'],
            'status'              => ['sometimes', 'required', 'in:open,in_progress,waiting,help_needed,completed,cancelled'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'due_date'            => ['nullable', 'date'],
            'related_order_id'      => ['nullable', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'related_customer_id'   => ['nullable', 'exists:customers,id'],
            'related_equipment_id'  => ['nullable', 'exists:equipment,id'],
        ];
    }
}

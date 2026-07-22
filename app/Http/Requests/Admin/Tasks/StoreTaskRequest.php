<?php

namespace App\Http\Requests\Admin\Tasks;

use App\Http\Requests\Admin\Tasks\Concerns\ResolvesRelatedOrderCustomer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    use ResolvesRelatedOrderCustomer;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'            => ['required', 'in:sales,admin,billing,yard,shop'],
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'priority'            => ['required', 'in:low,normal,high,urgent'],
            'status'              => ['required', 'in:open,in_progress,waiting,help_needed,completed,cancelled'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'due_date'            => ['nullable', 'date'],
            'related_order_id'      => ['nullable', Rule::exists('orders', 'id')->whereNull('deleted_at')],
            'related_customer_id'   => ['nullable', 'exists:customers,id'],
            'related_supplier_id'   => ['nullable', 'exists:suppliers,id'],
            'related_other'         => ['nullable', 'string', 'max:255'],
            'related_equipment_id'  => ['nullable', 'exists:equipment,id'],
            'media'                 => ['nullable', 'array', 'max:10'],
            'media.*'               => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm'],
        ];
    }
}

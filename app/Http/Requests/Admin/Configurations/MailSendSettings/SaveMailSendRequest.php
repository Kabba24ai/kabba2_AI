<?php

namespace App\Http\Requests\Admin\Configurations\MailSendSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\PurifyHelper;

class SaveMailSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Clean potential HTML/script input
        $this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'mail_mailer' => ['nullable', 'string', 'max:255'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'numeric', 'min:1'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'mail_mailer.required' => 'Please enter the mailer type.',
            'mail_host.required' => 'Please enter the mail host.',
            'mail_port.required' => 'Please enter the mail port.',
            'mail_encryption.in' => 'The encryption must be one of: tls, ssl, or none.',
            'mail_from_address.email' => 'The "From" address must be a valid email.',
        ];
    }
}

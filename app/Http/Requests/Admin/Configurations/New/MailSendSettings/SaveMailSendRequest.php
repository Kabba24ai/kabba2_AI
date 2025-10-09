<?php

namespace App\Http\Requests\Admin\Configurations\New\MailSendSettings;

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
        $input = PurifyHelper::purify($this->all(), [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ]);

        $this->merge($input);
    }

    public function rules(): array
    {
        return [
            'mail_mailer' => ['required', 'string', 'max:255'],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'numeric', 'min:1'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
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

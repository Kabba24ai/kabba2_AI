<?php

namespace App\Http\Requests\Admin\Configurations\GoogleMaps;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleMapsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Clean potential HTML/script input (same convention as the other
        // Configurations save requests).
        $this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'google_maps_enabled'       => ['nullable'],
            'google_maps_provider'      => ['nullable', 'string', 'max:50'],
            'google_maps_api_key'       => ['nullable', 'string', 'max:255'],
            'google_maps_traffic_aware' => ['nullable'],
            'google_maps_timeout'       => ['nullable', 'integer', 'between:1,120'],
            'google_maps_units'         => ['nullable', Rule::in(['imperial', 'metric'])],
        ];
    }

    public function messages(): array
    {
        return [
            'google_maps_timeout.integer' => 'Request timeout must be a whole number of seconds.',
            'google_maps_timeout.between' => 'Request timeout must be between 1 and 120 seconds.',
            'google_maps_units.in'        => 'Units must be either imperial or metric.',
        ];
    }
}

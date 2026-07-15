<?php

namespace App\Http\Requests\Admin\WebsiteManagement\HighDemandAlert;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Purify strips scripts/unsafe markup; the message keeps the app's
        // limited safe tags (strong/em/span/br etc.) for emphasis
        $this->merge(PurifyHelper::purify($this->except(['high_demand_alert_image'])));
    }

    public function rules(): array
    {
        return [
            'high_demand_alert_title'       => ['nullable', 'string', 'max:160'],
            'high_demand_alert_message'     => ['nullable', 'string', 'max:2000'],
            'high_demand_alert_phone'       => ['nullable', 'string', 'max:20'],
            'high_demand_alert_button_text' => ['nullable', 'string', 'max:60'],

            // New upload OR pick from the media library
            'high_demand_alert_image'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'high_demand_alert_image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],

            // Restore the built-in default image
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'high_demand_alert_image.mimes' => 'Alert image must be a JPG, PNG, WebP, or GIF file.',
            'high_demand_alert_image.max'   => 'Alert image may not exceed 2 MB.',
            'high_demand_alert_image_media_id.exists' => 'The selected image no longer exists in the media library.',
        ];
    }
}

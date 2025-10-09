<?php

namespace App\Http\Requests\Admin\Configurations\SocialMediaIntegration;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class SaveRequest extends FormRequest
{
    public function authorize()
    {
        // Only allow if user is authorized
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Clean potential HTML/script input
        $input = PurifyHelper::purify($this->all(), );
        $input['show_social_media_icons'] = $this->has('show_social_media_icons') ? true : false;
        $input['enable_social_sharing'] = $this->has('enable_social_sharing') ? true : false;

        $this->merge($input);
    }

    public function rules()
    {
        return [
            'social.facebook_page_link' => 'nullable|url',
            'social.twitter_page_link' => 'nullable|url',
            'social.instagram_page_link' => 'nullable|url',
            'social.linkedin_page_link' => 'nullable|url',
            'social.youtube_page_link' => 'nullable|url',
            'social.tiktok_page_link' => 'nullable|url',
            'social.pinterest_page_link' => 'nullable|url',
            'social.snapchat_page_link' => 'nullable|url',
            'social.show_social_media_icons' => 'nullable',
            'social.enable_social_sharing' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'social.*.url' => 'Please enter a valid URL.',
        ];
    }
}

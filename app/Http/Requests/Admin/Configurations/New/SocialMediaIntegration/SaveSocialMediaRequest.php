<?php

namespace App\Http\Requests\Admin\Configurations\New\SocialMediaIntegration;

use Illuminate\Foundation\Http\FormRequest;

class SaveSocialMediaRequest extends FormRequest
{
    public function authorize()
    {
        // Only allow if user is authorized
        return true;
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
            'social.show_social_media_icons' => 'nullable|boolean',
            'social.enable_social_sharing' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'social.*.url' => 'Please enter a valid URL.',
        ];
    }
}

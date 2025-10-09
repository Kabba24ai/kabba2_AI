<?php

namespace App\Http\Controllers\Admin\Configurations\New\SocialMediaIntegration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\New\SocialMediaIntegration\SaveSocialMediaRequest;
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\DB;

class SaveSocialMediaController extends Controller
{
    public function __invoke(SaveSocialMediaRequest $request)
    {
        $validated = $request->validated();

        // Define allowed settings mapping
        $map = [
            'facebook_page_link' => 'facebook_page_link',
            'twitter_page_link' => 'twitter_page_link',
            'instagram_page_link' => 'instagram_page_link',
            'linkedin_page_link' => 'linkedin_page_link',
            'youtube_page_link' => 'youtube_page_link',
            'tiktok_page_link' => 'tiktok_page_link',
            'pinterest_page_link' => 'pinterest_page_link',
            'snapchat_page_link' => 'snapchat_page_link',
            'show_social_media_icons' => 'show_social_media_icons',
            'enable_social_sharing' => 'enable_social_sharing',
        ];

        DB::transaction(function () use ($validated, $map) {
            foreach ($validated['social'] as $input => $value) {
                if (isset($map[$input])) {
                    Setting::updateOrCreate(
                        ['setting_name' => $map[$input]],
                        ['setting_value' => $value]
                    );
                }
            }
        });

        flash()->success(__('Social Media settings updated successfully.'));
        return redirect()->back();
    }
}

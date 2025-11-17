
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

{{-- Social Media Integration --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-share class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Social Media Integration</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Notice --}}
        <div class="mt-4 rounded-md p-4 md:col-span-2 text-left bg-blue-50 border border-blue-200 text-blue-900">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
                <div>
                    <h4 class="text-sm font-medium text-blue-800">Info</h4>
                    <p class="text-sm mt-1 text-blue-700">
                        Configure your social media presence. These links will appear on your website footer,
                        contact page, and can be used for social sharing functionality.
                    </p>
                </div>
            </div>
        </div>

        {{-- Social Media Links --}}
        @php
            $socialLinks = [
                'facebook_page_link' => 'Facebook Page Link',
                'twitter_page_link' => 'Twitter Page Link',
                'instagram_page_link' => 'Instagram URL',
                'linkedin_page_link' => 'LinkedIn Page Link',
                'youtube_page_link' => 'Youtube Page Link',
                'tiktok_page_link' => 'Tiktok Page Link',
                'pinterest_page_link' => 'Pinterest Page Link',
                'snapchat_page_link' => 'Snapchat Page Link',
            ];
        @endphp

        @foreach ($socialLinks as $key => $label)
            <div class="space-y-1.5">
                <label for="social_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1 text-left">
                    {{ $label }}
                </label>
                <div class="relative">
                    {!! html()->input('url', "social[$key]", $settings['Social Media Settings'][$key]['setting_value'] ?? '')->class([
                            'w-full rounded border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                            'dark:bg-gray-800 dark:text-white pr-10',
                        ])->attributes([
                            'placeholder' => $settings['Social Media Settings'][$key]['placeholder'] ?? "https://www.{$key}.com/yourpage",
                            'id' => "social_{$key}",
                        ]) !!}
                </div>
                @error("social.$key")
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        {{-- Display Settings --}}
        <div class="md:col-span-2 hidden">
            <div class="text-left">
                <h2 class="text-xl font-semibold text-gray-900 mt-4">Display Settings</h2>
            </div>
        </div>

        <div class="space-y-1.5 hidden">
            <div class="flex items-start space-x-2">
                <input type="hidden" name="social[show_social_media_icons]" value="0">
                <input class="h-4 w-4 text-blue-600 border-gray-300 rounded" type="checkbox"
                    name="social[show_social_media_icons]" id="show_social_media_icons" value="1"
                    {{ !empty($settings['Social Media Settings']['show_social_media_icons']['setting_value']) && $settings['Social Media Settings']['show_social_media_icons']['setting_value'] == 1 ? 'checked' : '' }}>
                <div>
                    <label for="show_social_media_icons" class="block font-medium text-gray-700 text-left leading-4">
                        Show Social Media Icons
                    </label>
                    <span class="block text-gray-500 text-sm mt-1">
                        Display social media icons in website footer and contact page
                    </span>
                </div>
            </div>
            @error('social.show_social_media_icons')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1.5 hidden">
            <div class="flex items-start space-x-2">
                <input type="hidden" name="social[enable_social_sharing]" value="0">
                <input class="h-4 w-4 text-blue-600 border-gray-300 rounded" type="checkbox"
                    name="social[enable_social_sharing]" id="enable_social_sharing" value="1"
                    {{ !empty($settings['Social Media Settings']['enable_social_sharing']['setting_value']) && $settings['Social Media Settings']['enable_social_sharing']['setting_value'] == 1 ? 'checked' : '' }}>
                <div>
                    <label for="enable_social_sharing" class="block font-medium text-gray-700 text-left leading-4">
                        Enable Social Sharing
                    </label>
                    <span class="block text-gray-500 text-sm mt-1">
                        Allow visitors to share your content on social media platforms
                    </span>
                </div>
            </div>
            @error('social.enable_social_sharing')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
{{-- End Social Media Integration --}}

</div>
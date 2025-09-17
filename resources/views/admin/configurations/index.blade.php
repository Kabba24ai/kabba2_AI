@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'System Configuration')

@section('content')


<div class="flex items-center justify-between mb-6">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">System Configuration</h3>
</div>

@include('flash::message')

{{ html()->form()->attributes([
            'autocomplete' => 'off',
            'data-parsley-validate' => true,
            'class' => 'space-y-6',
        ])->open() }}

@foreach ($settings as $type => $group)

@php
switch ($type) {
case 'Social Media Settings':
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share2 w-6 h-6 text-blue-600">
    <circle cx="18" cy="5" r="3"></circle>
    <circle cx="6" cy="12" r="3"></circle>
    <circle cx="18" cy="19" r="3"></circle>
    <line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line>
    <line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line>
</svg>';
break;

case 'Product Settings':
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-6 h-6 text-blue-600">
    <path d="m7.5 4.27 9 5.15"></path>
    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
    <path d="m3.3 7 8.7 5 8.7-5"></path>
    <path d="M12 22V12"></path>
</svg>';
break;

case 'Admin Settings':
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-cog w-6 h-6 text-blue-600">
    <circle cx="18" cy="15" r="3"></circle>
    <circle cx="9" cy="7" r="4"></circle>
    <path d="M10 15H6a4 4 0 0 0-4 4v2"></path>
    <path d="m21.7 16.4-.9-.3"></path>
    <path d="m15.2 13.9-.9-.3"></path>
    <path d="m16.6 18.7.3-.9"></path>
    <path d="m19.1 12.2.3-.9"></path>
    <path d="m19.6 18.7-.4-1"></path>
    <path d="m16.8 12.3-.4-1"></path>
    <path d="m14.3 16.6 1-.4"></path>
    <path d="m20.7 13.8 1-.4"></path>
</svg>';
break;

case 'Payment Settings':
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card w-6 h-6 text-blue-600">
    <rect width="20" height="14" x="2" y="5" rx="2"></rect>
    <line x1="2" x2="22" y1="10" y2="10"></line>
</svg>';
break;

case 'Contact Us Settings':
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone w-6 h-6 text-blue-600">
    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
</svg>';
break;

default:
// Default icog icon
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
    viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
    class="lucide lucide-clock w-6 h-6 text-blue-600">
    <circle cx="12" cy="12" r="10"></circle>
    <polyline points="12 6 12 12 16 14"></polyline>
</svg>';
}
@endphp


{{-- Card / Accordion --}}
<section x-data="{ open: true }" class="border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm  max-w-4xl mx-auto text-center">
    {{-- Card Header --}}
    <button type="button"
        @click="open = !open"
        class="w-full flex items-center justify-between gap-3 text-left
                           dark:bg-brand-900/10 px-6 sm:px-5 py-4 border-b
                           border-gray-200 dark:border-gray-800 hover:bg-gray-100">
        <span class="inline-flex items-center gap-3 text-lg font-semibold text-gray-800 dark:text-gray-100">

            {!! $icon !!}
            @php
            $typeEnum = \App\Enums\Configurations\SettingType::tryFrom($type) ?? \App\Enums\Configurations\SettingType::OTHER;
            @endphp

            {{ $typeEnum->label() }}
        </span>
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 transition-transform"
            :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Card Body --}}
    <div x-show="open" x-collapse class="bg-white dark:bg-gray-900 px-4 sm:px-6 py-5">

        @if ($type == 'Social Media Settings')
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-5 text-left">
            <div class="flex items-start space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share2 w-5 h-5 text-blue-600 mt-0.5">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line>
                    <line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-blue-800">Social Media Integration</h4>
                    <p class="text-sm text-blue-700 mt-1">Configure your social media presence. These links will appear on your website's footer, contact page, and can be used for social sharing functionality.</p>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($group as $setting)
            @php
            $inputValue = old("settings.{$setting->id}", $setting->setting_value);

            // Base classes (full width; let the grid control sizing)
            $baseClasses = "w-full rounded border border-gray-300 px-3 py-2 pr-20 text-sm
            focus:ring-2 focus:ring-brand-500 focus:border-brand-500
            dark:bg-gray-800 dark:text-white";

            if ($setting->setting_name === 'mobile') {
            $baseClasses .= ' masked-phone';
            }

            $errorClass = $errors->has("settings.{$setting->id}") ? 'border-red-500' : '';

            switch ($setting->value_type) {
            case 'number':
            $input = html()
            ->number("settings[{$setting->id}]")
            ->value($inputValue)
            ->class("$baseClasses $errorClass")
            ->id("setting_{$setting->id}")
            ->attributes([
            'data-parsley-type' => 'number',
            'placeholder' => $setting->placeholder,
            ]);
            break;

            case 'boolean':
            $input = html()
            ->select(
            "settings[{$setting->id}]",
            ['1' => 'Yes', '0' => 'No'],
            $inputValue,
            )
            ->class("$baseClasses $errorClass")
            ->id("setting_{$setting->id}");
            break;

            case 'options':
            $rawOptions = is_array($setting->setting_options)
            ? $setting->setting_options
            : json_decode($setting->setting_options, true) ?? [];
            $options = array_combine($rawOptions, $rawOptions);

            $input = html()
            ->select("settings[{$setting->id}]", $options, $inputValue)
            ->class("$baseClasses $errorClass")
            ->id("setting_{$setting->id}")
            ->placeholder('Select');
            break;

            case 'email':
            $input = html()
            ->email("settings[{$setting->id}]")
            ->value($inputValue)
            ->class("$baseClasses $errorClass")
            ->id("setting_{$setting->id}")
            ->attributes(['placeholder' => $setting->placeholder]);
            break;

            case 'textarea':
            $input = html()
            ->textarea("settings[{$setting->id}]")
            ->value($inputValue)
            ->class("$baseClasses $errorClass tinymce")
            ->id("setting_{$setting->id}")
            ->attributes([
            'placeholder' => $setting->placeholder,
            'rows' => 4,
            ]);
            break;

            case 'password':
            $input = html()
            ->password("settings[{$setting->id}]")
            ->value($inputValue)
            ->class("$baseClasses $errorClass")
            ->id("setting_{$setting->id}")
            ->attributes(['placeholder' => $setting->placeholder]);
            break;

            case 'checkbox':
            // Always send 0 when unchecked
            $hidden = '<input type="hidden" name="settings['.$setting->id.']" value="0">';

            $input = $hidden . html()
            ->checkbox("settings[{$setting->id}]", $inputValue == 1)
            ->class("h-4 w-4 text-brand-600 border-gray-300 rounded $errorClass")
            ->id("setting_{$setting->id}");
            break;



            default:
            $input = html()
            ->text("settings[{$setting->id}]")
            ->value($inputValue)
            ->class("$baseClasses $errorClass")
            ->id("setting_{$setting->id}")
            ->attributes(['placeholder' => $setting->placeholder]);
            }
            @endphp


            @php
            // If secure field, disable input by default
            if ($setting->is_secure_field) {
            $input = $input->attribute('disabled', true)->class('bg-gray-100');
            }
            @endphp



            {{-- Field --}}
            @if($setting->setting_title=="Prepaid Fuel Message")

            <div class="space-y-4 md:col-span-2 border-t border-gray-200 pt-6">
                <div class="flex items-center space-x-2  overflow-visible">
                    <h3 class="text-lg font-medium text-gray-900">Prepaid Fuel</h3>
                    <div class="relative group overflow-visible">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 text-gray-400 cursor-help">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">This feeds into a pop-up on the website when people deselect this option. <br> Pop-up appears with a message and 2 option buttons (Accept or Decline)
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($setting->setting_title=="Prepaid Cleaning Message")

            <div class="space-y-4 md:col-span-2 border-t border-gray-200 pt-6">
                <div class="flex items-center space-x-2  overflow-visible">
                    <h3 class="text-lg font-medium text-gray-900">Prepaid Cleaning</h3>
                    <div class="relative group overflow-visible">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 text-gray-400 cursor-help">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">This feeds into a pop-up on the website when people deselect this option.<br> Pop-up appears with a message and 2 option buttons (Accept or Decline)
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($setting->setting_title=="Facebook Page Link")
            <div class="md:col-span-2 ">
                <div class="text-left">
                    <h2 class="text-xl font-semibold text-gray-900">Social Media Profiles</h2>
                </div>
            </div>

            @elseif($setting->setting_title=="Show Social Media Icons")
            <div class="md:col-span-2 border-t border-gray-200 pt-6">
                <div class="text-left">
                    <h2 class="text-xl font-semibold text-gray-900">Display Settings</h2>
                </div>
            </div>

            @endif

            @if($setting->setting_title=="Damage Waiver Protection Message")

            <div class="space-y-4 md:col-span-2 border-t border-gray-200 pt-6">
                <div class="flex items-center space-x-2  overflow-visible">
                    <h3 class="text-lg font-medium text-gray-900">Damage Waiver Protection</h3>
                    <div class="relative group overflow-visible">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 text-gray-400 cursor-help">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">This feeds into a pop-up on the website when people deselect this option. <br> Pop-up appears with a message and 2 option buttons (Accept or Decline)
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($setting->setting_title=="Thrown Track Insurance Message")

            <div class="space-y-4 md:col-span-2 border-t border-gray-200 pt-6">
                <div class="flex items-center space-x-2  overflow-visible">
                    <h3 class="text-lg font-medium text-gray-900">Thrown Track Insurance</h3>
                    <div class="relative group overflow-visible">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 text-gray-400 cursor-help">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">This feeds into a pop-up on the website when people deselect this option. <br> Pop-up appears with a message and 2 option buttons (Accept or Decline)
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif



            <div class="space-y-1.5 {{ ($setting->value_type === 'textarea' || $setting->setting_name === 'sales_tax' ) ? 'md:col-span-2' : '' }}">

                @if($setting->setting_title=="Show Social Media Icons" || $setting->setting_title=="Enable Social Sharing")
                <div class="flex items-start space-x-2">
                    {!! $input !!}

                    {{-- Eye toggle if enabled --}}
                    @if($setting->is_eye_toggle)
                    <button type="button"
                        class="pw-toggle absolute inset-y-0 right-10 px-2 text-gray-500 hover:text-gray-700 disabled-eye"
                        data-target="#setting_{{ $setting->id }}"
                        aria-pressed="false" title="Show/Hide passcode">

                        {{-- Eye icon --}}
                        <svg data-eye xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-eye w-5 h-5">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>

                        {{-- Eye-off icon --}}
                        <svg data-eye-off xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-eye-off w-5 h-5 hidden">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                            <line x1="2" x2="22" y1="2" y2="22"></line>
                        </svg>
                    </button>
                    @endif
                    {{-- Lock button only for secure groups --}}
                    {{-- Lock button if secure --}}
                    @if($setting->is_secure_field)
                    <button type="button"
                        class="opensecurityModal lock-trigger absolute inset-y-0 right-2 px-2 flex items-center text-blue-600 hover:text-gray-600 lock-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-lock w-4 h-4">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </button>
                    @endif

                    <div>
                        <label for="setting_{{ $setting->id }}"
                            class="block font-medium text-gray-700 text-left leading-4">
                            {{ $setting->setting_title }}
                        </label>


                        <span class="block text-gray-500 text-sm mt-1">
                            @if($setting->setting_title=="Enable Social Sharing")
                            Allow visitors to share your content on social media platforms
                            @elseif($setting->setting_title=="Show Social Media Icons")
                            Display social media icons in website footer and contact page
                            @endif
                        </span>
                    </div>

                </div>

                <span class="parsley-errors-list"></span>
                @error("settings.{$setting->id}")
                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror



                @else

                @if($setting->setting_name !== 'include_extended_range')

                <label for="setting_{{ $setting->id }}"
                    class="block text-sm font-medium text-gray-700 mb-1 text-left ">

                    {{ $setting->setting_title }}

                    @if ($setting->is_required)
                    <span class="text-red-500 ml-1">*</span>
                    @endif

                    @if ($setting->is_encrypted)
                    <span class="text-blue-600 ml-2 text-xs">(Encrypted)</span>
                    @endif

                </label>

                @endif

                <div class=" @if($setting->setting_name == 'include_extended_range') flex items-start space-x-2 @else relative @endif">


                    @if ($setting->setting_name == "sales_tax" )
                    <div class="space-y-2">

                        <div class="flex items-center space-x-2">
                            <input type="number" placeholder="{{ $setting->placeholder ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" step="any" min="0" max="100" value="{{ $setting->setting_value }}" name="settings[{{ $setting->id}}]" id="setting_{{ $setting->id}}">
                            <span class="text-sm text-gray-500">%</span>
                        </div>
                        <p class="text-sm text-gray-500 text-left">Enter percentage (e.g., 8.25 for 8.25%)</p>
                    </div>

                    @else
                    {!! $input !!}
                    @endif

                    @if($setting->setting_name == 'include_extended_range')

                    <label for="setting_{{ $setting->id }}"
                        class="block text-sm font-medium text-gray-700 mb-1 text-left ">

                        {{ $setting->setting_title }}

                        @if ($setting->is_required)
                        <span class="text-red-500 ml-1">*</span>
                        @endif

                        @if ($setting->is_encrypted)
                        <span class="text-blue-600 ml-2 text-xs">(Encrypted)</span>
                        @endif

                    </label>

                    @endif

                    {{-- Eye toggle if enabled --}}
                    @if($setting->is_eye_toggle)
                    <button type="button"
                        class="pw-toggle absolute inset-y-0 right-10 px-2 text-gray-500 hover:text-gray-700 disabled-eye"
                        data-target="#setting_{{ $setting->id }}"
                        aria-pressed="false" title="Show/Hide passcode">

                        {{-- Eye icon --}}
                        <svg data-eye xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-eye w-5 h-5">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>

                        {{-- Eye-off icon --}}
                        <svg data-eye-off xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-eye-off w-5 h-5 hidden">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                            <line x1="2" x2="22" y1="2" y2="22"></line>
                        </svg>
                    </button>
                    @endif
                    {{-- Lock button only for secure groups --}}
                    @if($setting->is_secure_field)
                    <button type="button"
                        class="opensecurityModal lock-trigger absolute inset-y-0 right-2 px-2 flex items-center text-blue-600 hover:text-gray-600 lock-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-lock w-4 h-4">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </button>
                    @endif


                </div>

                <span class="parsley-errors-list"></span>
                @error("settings.{$setting->id}")
                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                @endif


            </div>
            @if($setting->setting_title=="Enable Social Sharing")
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4 md:col-span-2 text-left">
                <h3 class="text-sm font-medium text-gray-800 mb-3">Preview</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Social media links that have URLs will appear as clickable icons on your website:
                </p>
                <p class="mt-1 text-sm italic text-gray-400">
                    Add social media URLs to see preview icons
                </p>
            </div>
            @endif
            @endforeach
        </div>

        @if ($type == 'Admin Settings')
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 text-amber-900 text-left p-3">
            <div class="flex items-start space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-5 h-5 text-amber-600 mt-0.5">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Security Notice</h4>
                    <p class="text-sm text-amber-700 mt-1">Both fields are critical for system security. The Master Passcode is encrypted and the Master Password is required to access/edit it. Always use strong, unique passwords and store them securely.</p>
                </div>
            </div>
        </div>
        @endif


        @if ($type == 'Payment Settings')
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 text-amber-900 text-left p-3">
            <div class="flex items-start space-x-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-5 h-5 text-amber-600 mt-0.5">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Security Notice</h4>
                    <p class="text-sm text-amber-700 mt-1">Payment integration settings contain sensitive API keys and credentials. Master Passcode verification is required to view or modify these settings for security purposes.</p>
                </div>
            </div>
        </div>
        @endif

        @if ($type == 'Contact Us Settings')
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mt-5">
            <p class="text-sm text-blue-800"><strong>Note:</strong> Store locations/addresses that appear on the Contact Us page are managed in Store Settings.<a href="{{ route('admin.stores.index') }}" class="text-blue-600 hover:text-blue-800 underline ml-1">Go to Store Settings page</a></p>
        </div>

        @endif

    </div>
</section>
@endforeach

{{-- Footer actions --}}
<div class="mt-8  max-w-4xl mx-auto flex justify-end space-x-4 p-6 bg-white rounded-lg shadow-sm border border-gray-200">
    <button type="button" id="resetBtn"
        class="inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-blue-500">
        Reset to Defaults
    </button>
    <button type="submit" name="action" value="save" class="inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed border-transparent text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
            <polyline points="17 21 17 13 7 13 7 21"></polyline>
            <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        Save All Settings
    </button>
</div>



{{ html()->form()->close() }}

<form id="reset-form" method="POST" action="{{ route('admin.configurations.settings.reset') }}" class="hidden">
    @csrf
</form>


<div id="securityModalWrapper" style="display: none;" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 flex flex-col max-h-full">
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-6 h-6 text-red-600">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Security Verification Required</h2>
                </div>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div class=" px-6 overflow-y-auto">
                <div class="w-[92vw] max-w-md bg-white">
                    <div class="flex items-start gap-2 mb-2">
                        <p class="text-sm text-gray-600">Enter your Master Password to edit the Master Passcode.</p>
                    </div>

                    <div class="relative mt-3">
                        <input id="masterPwd" type="password" placeholder="Enter your Master Password" class="w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm shadow-sm"
                            autocomplete="current-password" />
                        <button type="button" id="pwdEye" class="absolute inset-y-0 right-2 flex items-center px-2 text-gray-500 hover:text-gray-700" aria-label="Show/Hide password" aria-pressed="false">
                            <svg id="eyeOn" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye w-5 h-5">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>

                            <svg id="eyeOff" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off w-5 h-5 hidden">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                <line x1="2" x2="22" y1="2" y2="22"></line>
                            </svg>


                        </button>
                    </div>

                    <div class="mt-4 flex flex-col sm:flex-row gap-2 pb-4">
                        <button id="verifyBtn" type="button" disabled class="inline-flex items-center justify-center rounded px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-unlock w-4 h-4 mr-2">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                            </svg>
                            Verify & Edit
                        </button>

                        <button type="button" id="canceltempBtn" class="inline-flex items-center justify-center gap-2 rounded px-4 py-2 text-sm border border-gray-300 bg-white hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 8.586l4.95-4.95 1.414 1.414L11.414 10l4.95 4.95-1.414 1.414L10 11.414l-4.95 4.95-1.414-1.414L8.586 10l-4.95-4.95L5.05 3.636 10 8.586z" clip-rule="evenodd" />
                            </svg>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')

<script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
@vite('resources/admin/js/tinymce.js')

<script>
    document.getElementById('resetBtn').addEventListener('click', function() {
        window.showConfirm(
            "Reset all settings to default? This action cannot be undone!",
            "Reset Settings"
        ).then(result => {
            if (result.isConfirmed) {
                document.getElementById('reset-form').submit();
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalWrapper = document.getElementById('securityModalWrapper');
        // const openBtn = document.getElementById('opensecurityModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('canceltempBtn');

        // Open modal from ANY .opensecurityModal button
        document.querySelectorAll('.opensecurityModal').forEach(button => {
            button.addEventListener('click', () => {
                modalWrapper.style.display = 'flex';
            });
        });


        // openBtn.addEventListener('click', () => {
        //     modalWrapper.style.display = 'flex';
        // });

        const closeModal = () => {
            modalWrapper.style.display = 'none';
        };

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) {
                closeModal();
            }
        });
    });
</script>

<script>
    (function() {
        const pwd = document.getElementById('masterPwd');
        const btn = document.getElementById('verifyBtn');
        const eye = document.getElementById('pwdEye');
        const eyeOn = document.getElementById('eyeOn');
        const eyeOff = document.getElementById('eyeOff');

        function updateState() {
            const hasValue = pwd.value.trim().length > 0;
            btn.disabled = !hasValue;
        }
        pwd.addEventListener('input', updateState);
        updateState();

        eye.addEventListener('click', () => {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            eye.setAttribute('aria-pressed', String(show));
            eyeOn.classList.toggle('hidden', show);
            eyeOff.classList.toggle('hidden', !show);
            pwd.focus();
            const val = pwd.value;
            pwd.value = '';
            pwd.value = val;
        });
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const verifyBtn = document.getElementById('verifyBtn');
        const pwd = document.getElementById('masterPwd');
        const modalWrapper = document.getElementById('securityModalWrapper');
        let currentFieldId = null;

        // Disable eye toggle if input is locked
        document.querySelectorAll('.pw-toggle').forEach(btn => {
            const input = document.querySelector(btn.dataset.target);
            if (input && input.disabled) {
                btn.classList.add('pointer-events-none', 'opacity-50'); // disable click
                btn.querySelector('[data-eye]').classList.add('hidden');
                btn.querySelector('[data-eye-off]').classList.remove('hidden');
            }
        });

        // Lock button click → open modal
        document.querySelectorAll('.opensecurityModal').forEach(button => {
            button.addEventListener('click', () => {
                currentFieldId = button.closest('.relative')
                    .querySelector('input, select, textarea')
                    ?.id.replace('setting_', '');
                modalWrapper.style.display = 'flex';
            });
        });

        // Verify Master Password
        verifyBtn.addEventListener('click', () => {
            if (!currentFieldId) return;

            fetch("{{ route('admin.configurations.verifyMaster') }}", {
                    method: 'POST',
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        password: pwd.value,
                        field_id: currentFieldId
                    }),
                })
                .then(res => res.json().then(data => ({
                    ok: res.ok,
                    data
                })))
                .then(({
                    ok,
                    data
                }) => {
                    if (ok && data.status === 'success') {
                        const field = document.getElementById(`setting_${data.field_id}`);
                        if (field) {
                            // Enable input
                            field.removeAttribute('disabled');
                            field.classList.remove('bg-gray-100');

                            // Enable eye toggle if present
                            const eyeBtn = field.closest('.relative').querySelector('.pw-toggle');
                            if (eyeBtn) {
                                eyeBtn.classList.remove('pointer-events-none', 'opacity-50');
                            }

                            // Change lock → unlock
                            const lockWrapper = field.closest('.relative').querySelector('.lock-wrapper');
                            if (lockWrapper) {
                                lockWrapper.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-unlock w-4 h-4 mr-2">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                            </svg>
                        `;
                            }
                        }
                        modalWrapper.style.display = 'none';
                        pwd.value = '';
                        notyf.success(data.message);
                    } else {
                        notyf.error(data.message || 'Verification failed');
                    }
                })
                .catch(() => notyf.error('Something went wrong.'));
        });


    });
</script>

<script>
    document.querySelectorAll('.pw-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.querySelector(btn.dataset.target);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', String(show));
            btn.querySelector('[data-eye]').classList.toggle('hidden', show);
            btn.querySelector('[data-eye-off]').classList.toggle('hidden', !show);
        });
    });

    (function() {
        const btn = document.getElementById('adminCardBtn');
        const body = document.getElementById('adminCardBody');
        const chev = document.getElementById('adminChevron');
        btn.addEventListener('click', (e) => {
            if (e.target.closest('.pw-toggle')) return; // don't collapse when clicking eye
            const open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!open));
            body.classList.toggle('hidden', open);
            chev.classList.toggle('rotate-180', !open);
        });
    })();
</script>
@endpush

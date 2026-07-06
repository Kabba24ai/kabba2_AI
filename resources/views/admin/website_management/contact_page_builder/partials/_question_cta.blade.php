@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';
    $content     = $section?->content ?? [];

    $ctaIcons = [
        ['class' => 'heroicon-o-chat-bubble-left-ellipsis', 'label' => 'Chat'],
        ['class' => 'heroicon-o-phone',                     'label' => 'Phone'],
        ['class' => 'heroicon-o-envelope',                  'label' => 'Email'],
        ['class' => 'heroicon-o-question-mark-circle',      'label' => 'Help'],
        ['class' => 'heroicon-o-information-circle',        'label' => 'Info'],
        ['class' => 'heroicon-o-lifebuoy',                  'label' => 'Support'],
        ['class' => 'heroicon-o-hand-raised',               'label' => 'Hand'],
        ['class' => 'heroicon-o-users',                     'label' => 'Team'],
        ['class' => 'heroicon-o-building-office-2',         'label' => 'Office'],
        ['class' => 'heroicon-o-map-pin',                   'label' => 'Location'],
        ['class' => 'heroicon-o-clock',                     'label' => 'Hours'],
        ['class' => 'heroicon-o-shield-check',              'label' => 'Trust'],
        ['class' => 'heroicon-o-star',                      'label' => 'Star'],
        ['class' => 'heroicon-o-bolt',                      'label' => 'Fast'],
        ['class' => 'heroicon-o-check-circle',              'label' => 'Done'],
        ['class' => 'heroicon-o-sparkles',                  'label' => 'Sparkles'],
        ['class' => 'heroicon-o-rocket-launch',             'label' => 'Rocket'],
        ['class' => 'heroicon-o-trophy',                    'label' => 'Trophy'],
        ['class' => 'heroicon-o-gift',                      'label' => 'Gift'],
        ['class' => 'heroicon-o-heart',                     'label' => 'Heart'],
        ['class' => 'heroicon-o-face-smile',                'label' => 'Smile'],
        ['class' => 'heroicon-o-light-bulb',                'label' => 'Idea'],
        ['class' => 'heroicon-o-currency-dollar',           'label' => 'Pricing'],
        ['class' => 'heroicon-o-truck',                     'label' => 'Delivery'],
        ['class' => 'heroicon-o-wrench-screwdriver',        'label' => 'Service'],
        ['class' => 'icon-headset',                         'label' => 'Headset'],
    ];
@endphp

@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes>
    @csrf

    {{-- Header + status toggle + save --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5"
         x-data="{ active: @js($isActive) }">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Question CTA</h3>
            <p class="text-xs text-gray-500 mt-0.5">The call-to-action banner shown below the locations section.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="status"
                   value="{{ $isActive ? 'Active' : 'Inactive' }}"
                   :value="active ? 'Active' : 'Inactive'">
            <div class="flex items-center gap-2 cursor-pointer select-none" @click="active = !active">
                <button type="button"
                        :class="active ? 'bg-green-500' : 'bg-gray-300'"
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none">
                    <span :class="active ? 'translate-x-4' : 'translate-x-0.5'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"></span>
                </button>
                <span class="text-xs font-medium w-14"
                      :class="active ? 'text-green-600' : 'text-gray-400'"
                      x-text="active ? 'Active' : 'Inactive'"></span>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                Save Section
            </button>
        </div>
    </div>

    {{-- Fields --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- Heading --}}
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Heading <span class="text-red-500">*</span></label>
            <input type="text" name="title"
                   value="{{ old('title', $section->title ?? 'Have a question?') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                   placeholder="Have a question?">
            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="subtitle" rows="2"
                      class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none resize-none"
                      placeholder="Our team is ready to help you find the right equipment for your project.">{{ old('subtitle', $section->subtitle ?? '') }}</textarea>
            @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Button Text --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
            <input type="text" name="button_text"
                   value="{{ old('button_text', $section->button_text ?? 'Call Main Sales Line') }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                   placeholder="Call Main Sales Line">
            @error('button_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Phone Number with mask --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Phone Number
                <span class="ml-1 text-xs font-normal text-gray-400">(shown below button)</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-400"/>
                </span>
                <input type="text"
                       name="content[phone_number]"
                       value="{{ old('content.phone_number', $content['phone_number'] ?? '') }}"
                       class="masked-phone w-full border border-gray-300 rounded-md pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                       placeholder="(615) 000-0000"
                       autocomplete="tel">
            </div>
            @error('content.phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Icon Picker --}}
        <div>
            @include('admin.website_management.home_page_builder.partials._icon_picker', [
                'pickerIcons' => $ctaIcons,
                'currentIcon' => old('content.icon', $content['icon'] ?? 'heroicon-o-chat-bubble-left-ellipsis'),
                'inputName'   => 'content[icon]',
            ])
            @error('content.icon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

    </div>
</form>
@else
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-800">
    Question CTA section not found. Run the seeder first.
</div>
@endif

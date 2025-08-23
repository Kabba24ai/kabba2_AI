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
        {{-- Card / Accordion --}}
        <section x-data="{ open: true }" class="border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm overflow-hidden">
            {{-- Card Header --}}
            <button type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between gap-3 text-left
                           bg-brand-50/60 dark:bg-brand-900/10 px-4 sm:px-5 py-3 border-b
                           border-gray-200 dark:border-gray-800">
                <span class="inline-flex items-center gap-2 font-semibold text-gray-800 dark:text-gray-100">
                    <x-heroicon-m-cog-6-tooth class="w-5 h-5" />
                    {{ $type }}
                </span>
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 transition-transform"
                     :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                </svg>
            </button>

            {{-- Card Body --}}
            <div x-show="open" x-collapse class="bg-white dark:bg-gray-900 px-4 sm:px-6 py-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach ($group as $setting)
                        @php
                            $inputValue = old("settings.{$setting->id}", $setting->setting_value);

                            // Base classes (full width; let the grid control sizing)
                            $baseClasses = "w-full rounded border border-gray-300 px-3 py-2 text-sm
                                            focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                            dark:bg-gray-800 dark:text-white";

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
                                            'placeholder' => 'Enter Here',
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
                                        ->attributes(['placeholder' => 'Enter Here']);
                                    break;

                                case 'textarea':
                                    $input = html()
                                        ->textarea("settings[{$setting->id}]")
                                        ->value($inputValue)
                                        ->class("$baseClasses $errorClass")
                                        ->id("setting_{$setting->id}")
                                        ->attributes([
                                            'placeholder' => 'Enter Here',
                                            'rows' => 4,
                                        ]);
                                    break;

                                case 'password':
                                    $input = html()
                                        ->password("settings[{$setting->id}]")
                                        ->value($inputValue)
                                        ->class("$baseClasses $errorClass")
                                        ->id("setting_{$setting->id}")
                                        ->attributes(['placeholder' => 'Enter Here']);
                                    break;

                                default:
                                    $input = html()
                                        ->text("settings[{$setting->id}]")
                                        ->value($inputValue)
                                        ->class("$baseClasses $errorClass")
                                        ->id("setting_{$setting->id}")
                                        ->attributes(['placeholder' => 'Enter Here']);
                            }
                        @endphp

                        {{-- Field --}}
                        <div class="space-y-1.5">
                            <label for="setting_{{ $setting->id }}"
                                   class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $setting->setting_title }}
                            </label>

                            {!! $input !!}

                            <span class="parsley-errors-list"></span>
                            @error("settings.{$setting->id}")
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    {{-- Footer actions --}}
    <div class="flex flex-wrap justify-end gap-3 pt-4">
        <button type="submit" name="action" value="save"
                class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
            Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
        </button>
    </div>

    {{ html()->form()->close() }}
@endsection

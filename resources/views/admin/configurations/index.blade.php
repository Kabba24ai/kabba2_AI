@extends('admin.layouts.app')

@section('title', 'System Configuration')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">System Configuration</h3>
    </div>

    @include('flash::message')

    <div class="flex flex-col lg:grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="px-2 md:px-6 py-6">
                {{ html()->form()->attributes([
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'space-y-8',
                    ])->open() }}

                @foreach ($settings as $type => $group)
                    <div class="mb-8">
                        <div class="mb-2 text-base font-semibold text-gray-800 dark:text-gray-200">{{ $type }}</div>
                        <div
                            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm px-4 sm:px-8 py-6">
                            <div class="flex flex-col gap-4">
                                @foreach ($group as $setting)
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                                        <label for="setting_{{ $setting->id }}"
                                            class="block text-gray-700 dark:text-gray-300 text-sm sm:w-64 font-medium">
                                            {{ $setting->setting_title }}
                                        </label>
                                        <div class="w-full sm:w-auto flex flex-col">
                                            @php
                                                $input = null;
                                                $inputClasses = [
                                                    'rounded border border-gray-300 px-3 py-1 text-sm focus:ring-2 focus:border-brand-500 dark:bg-gray-800 dark:text-white',
                                                    'w-full sm:w-36' => $setting->value_type === 'number',
                                                    'w-full sm:w-48' => $setting->value_type === 'options',
                                                    'w-full sm:w-28' => $setting->value_type === 'boolean',
                                                    'border-red-500' => $errors->has("settings.{$setting->id}"),
                                                ];
                                                if ($setting->value_type === 'number') {
                                                    $input = html()
                                                        ->number("settings[{$setting->id}]")
                                                        ->value(old("settings.{$setting->id}", $setting->setting_value))
                                                        ->class($inputClasses)
                                                        ->id("setting_{$setting->id}")
                                                        ->attributes([
                                                            'data-parsley-type' => 'number',
                                                            'placeholder' => 'Enter Here',
                                                        ]);
                                                } elseif ($setting->value_type === 'boolean') {
                                                    $input = html()
                                                        ->select(
                                                            "settings[{$setting->id}]",
                                                            ['1' => 'Yes', '0' => 'No'],
                                                            old("settings.{$setting->id}", $setting->setting_value),
                                                        )
                                                        ->class($inputClasses)
                                                        ->id("setting_{$setting->id}");
                                                } elseif ($setting->value_type === 'options') {
                                                    $options = is_array($setting->setting_options)
                                                        ? $setting->setting_options
                                                        : json_decode($setting->setting_options, true) ?? [];
                                                    $input = html()
                                                        ->select(
                                                            "settings[{$setting->id}]",
                                                            $options,
                                                            old("settings.{$setting->id}", $setting->setting_value),
                                                        )
                                                        ->class($inputClasses)
                                                        ->id("setting_{$setting->id}")
                                                        ->placeholder('Select');
                                                } else {
                                                    $input = html()
                                                        ->text("settings[{$setting->id}]")
                                                        ->value(old("settings.{$setting->id}", $setting->setting_value))
                                                        ->class($inputClasses)
                                                        ->id("setting_{$setting->id}")
                                                        ->attributes([
                                                            'placeholder' => 'Enter Here',
                                                        ]);
                                                }
                                            @endphp
                                            {!! $input !!}
                                            <span class="parsley-errors-list"></span>
                                            @error("settings.{$setting->id}")
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="flex flex-wrap justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button type="submit" name="action" value="save"
                        class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                        Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
                    </button>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
@endsection

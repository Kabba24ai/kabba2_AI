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
                                        {{-- LABEL --}}
                                        <label for="setting_{{ $setting->id }}"
                                            class="block text-gray-700 dark:text-gray-300 text-sm font-medium
                       sm:w-56 md:w-64 lg:w-72 flex-shrink-0">
                                            {{ $setting->setting_title }}
                                        </label>

                                        {{-- INPUT --}}
                                        <div class="w-full sm:flex-1 flex flex-col">
                                            @php
                                                $inputValue = old("settings.{$setting->id}", $setting->setting_value);

                                                // Choose input width based on value type
                                                $inputWidth = match ($setting->value_type) {
                                                    'number', 'options', 'boolean' => 'sm:w-36 md:w-40',
                                                    default => 'sm:w-60 md:w-72',
                                                };

                                                $inputClasses =
                                                    "rounded border border-gray-300 px-3 py-1 text-sm
                        focus:ring-2 focus:border-brand-500 dark:bg-gray-800 dark:text-white
                        $inputWidth " .
                                                    ($errors->has("settings.{$setting->id}") ? 'border-red-500' : '');

                                                switch ($setting->value_type) {
                                                    case 'number':
                                                        $input = html()
                                                            ->number("settings[{$setting->id}]")
                                                            ->value($inputValue)
                                                            ->class($inputClasses)
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
                                                            ->class($inputClasses)
                                                            ->id("setting_{$setting->id}");
                                                        break;
                                                    case 'options':
                                                        $rawOptions = is_array($setting->setting_options)
                                                            ? $setting->setting_options
                                                            : json_decode($setting->setting_options, true) ?? [];

                                                        $options = array_combine($rawOptions, $rawOptions);

                                                        $input = html()
                                                            ->select("settings[{$setting->id}]", $options, $inputValue)
                                                            ->class($inputClasses)
                                                            ->id("setting_{$setting->id}")
                                                            ->placeholder('Select');
                                                        break;

                                                    case 'email':
                                                        $input = html()
                                                            ->email("settings[{$setting->id}]")
                                                            ->value($inputValue)
                                                            ->class($inputClasses)
                                                            ->id("setting_{$setting->id}")
                                                            ->attributes(['placeholder' => 'Enter Here']);
                                                        break;
                                                    case 'textarea':
                                                        $input = html()
                                                            ->textarea("settings[{$setting->id}]")
                                                            ->value($inputValue)
                                                            ->class($inputClasses)
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
                                                            ->class($inputClasses)
                                                            ->id("setting_{$setting->id}")
                                                            ->attributes(['placeholder' => 'Enter Here']);
                                                        break;
                                                    default:
                                                        $input = html()
                                                            ->text("settings[{$setting->id}]")
                                                            ->value($inputValue)
                                                            ->class($inputClasses)
                                                            ->id("setting_{$setting->id}")
                                                            ->attributes(['placeholder' => 'Enter Here']);
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

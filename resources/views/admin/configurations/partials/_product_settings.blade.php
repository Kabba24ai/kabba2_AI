@push('css')
    <style>
        .tox-tinymce {
            max-height: 15rem;
        }
    </style>
@endpush
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-currency-dollar class="w-5 h-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Range & Sales Tax Rate</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
            <label for="standard_delivery_range" class="block text-sm font-medium text-gray-700 mb-1">
                Standard Delivery Range
            </label>

            <div class="relative">
                {!! html()->input(
                        'number',
                        'standard_delivery_range',
                        $settings['Product Settings']['standard_delivery_range']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('standard_delivery_range'),
                        'border-red-500' => $errors->has('standard_delivery_range'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['standard_delivery_range']['placeholder'],
                        'required' => true,
                        'id' => 'standard_delivery_range',
                    ]) !!}
            </div>

            @error('standard_delivery_range')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="distance_unit" class="block text-sm font-medium text-gray-700 mb-1">
                Distance Unit
            </label>

            <div class="relative">
                {!! html()->select(
                        'distance_unit',
                        [
                            'Kilometers' => 'Kilometers',
                            'Miles' => 'Miles',
                        ],
                        $settings['Product Settings']['distance_unit']['setting_value'],
                    )->class([
                        'w-28 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('distance_unit'),
                        'border-red-500' => $errors->has('distance_unit'),
                    ])->attributes([
                        'id' => 'distance_unit',
                        'required' => true,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['distance_unit']['placeholder'] ?? '',
                    ]) !!}
            </div>

            @error('distance_unit')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="sales_tax" class="block text-sm font-medium text-gray-700 mb-1">
                Sales Tax Rate (%)
            </label>

            <div class="relative">
                {!! html()->input(
                        'text',
                        'sales_tax',
                        \App\Helpers\CustomHelper::displayPercentage($settings['Product Settings']['sales_tax']['setting_value']),
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('sales_tax'),
                        'border-red-500' => $errors->has('sales_tax'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['sales_tax']['placeholder'],
                        'required' => true,
                        'id' => 'sales_tax',
                    ]) !!}
            </div>

            @error('sales_tax')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="extended_delivery_range" class="block text-sm font-medium text-gray-700 mb-1">
                Extended Delivery Range
            </label>

            <div class="relative">
                {!! html()->input(
                        'number',
                        'extended_delivery_range',
                        $settings['Product Settings']['extended_delivery_range']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('extended_delivery_range'),
                        'border-red-500' => $errors->has('extended_delivery_range'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['extended_delivery_range']['placeholder'],
                        'required' => true,
                        'id' => 'extended_delivery_range',
                    ]) !!}
            </div>

            @error('extended_delivery_range')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="include_extended_range" class="block text-sm font-medium text-gray-700 mb-1">
                Extended Range Option
            </label>

            <div class="relative">
                {!! html()->checkbox(
                        'include_extended_range',
                        $settings['Product Settings']['include_extended_range']['setting_value'] == '1',
                        1,
                    )->class([
                        'h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500',
                        'border-red-500' => $errors->has('include_extended_range'),
                    ])->attributes([
                        'id' => 'include_extended_range',
                    ]) !!}
                <span class="ml-2 text-sm text-gray-700">Include</span>
            </div>

            @error('include_extended_range')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

@include('admin.configurations.partials._allocated_settings')

@include('admin.configurations.partials._product_rate_settings')

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Prepaid Fuel</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="prepaid_fuel_decline_label" class="block text-sm font-medium text-gray-700 mb-1">
                Decline Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'prepaid_fuel_decline_label',
                        old(
                            'prepaid_fuel_decline_label',
                            $settings['Product Settings']['prepaid_fuel_decline_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('prepaid_fuel_decline_label'),
                        'border-red-500' => $errors->has('prepaid_fuel_decline_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'prepaid_fuel_decline_label',
                    ])->required() !!}
            </div>

            @error('prepaid_fuel_decline_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="prepaid_fuel_approve_label" class="block text-sm font-medium text-gray-700 mb-1">
                Accept Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'prepaid_fuel_approve_label',
                        old(
                            'prepaid_fuel_approve_label',
                            $settings['Product Settings']['prepaid_fuel_approve_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('prepaid_fuel_approve_label'),
                        'border-red-500' => $errors->has('prepaid_fuel_approve_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'prepaid_fuel_approve_label',
                    ])->required() !!}
            </div>

            @error('prepaid_fuel_approve_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="prepaid_fuel_info" class="block text-sm font-medium text-gray-700 mb-1">
                Prepaid Fuel Message
            </label>

            <div class="relative">
                {!! html()->textarea('prepaid_fuel_info', $settings['Product Settings']['prepaid_fuel_info']['setting_value'])->class([
                        'tinymce w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('prepaid_fuel_info'),
                        'border-red-500' => $errors->has('prepaid_fuel_info'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['prepaid_fuel_info']['placeholder'],
                        'required' => true,
                        'id' => 'prepaid_fuel_info',
                    ]) !!}
            </div>

            @error('prepaid_fuel_info')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Prepaid Cleaning</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="prepaid_cleaning_decline_label" class="block text-sm font-medium text-gray-700 mb-1">
                Decline Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'prepaid_cleaning_decline_label',
                        old(
                            'prepaid_cleaning_decline_label',
                            $settings['Product Settings']['prepaid_cleaning_decline_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('prepaid_cleaning_decline_label'),
                        'border-red-500' => $errors->has('prepaid_cleaning_decline_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'prepaid_cleaning_decline_label',
                    ])->required() !!}
            </div>

            @error('prepaid_cleaning_decline_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="prepaid_cleaning_approve_label" class="block text-sm font-medium text-gray-700 mb-1">
                Accept Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'prepaid_cleaning_approve_label',
                        old(
                            'prepaid_cleaning_approve_label',
                            $settings['Product Settings']['prepaid_cleaning_approve_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('prepaid_cleaning_approve_label'),
                        'border-red-500' => $errors->has('prepaid_cleaning_approve_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'prepaid_cleaning_approve_label',
                    ])->required() !!}
            </div>

            @error('prepaid_cleaning_approve_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="prepaid_cleaning_info" class="block text-sm font-medium text-gray-700 mb-1">
                Prepaid Cleaning Message
            </label>

            <div class="relative max-h-52">
                {!! html()->textarea('prepaid_cleaning_info', $settings['Product Settings']['prepaid_cleaning_info']['setting_value'])->class([
                        'tinymce w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('prepaid_cleaning_info'),
                        'border-red-500' => $errors->has('prepaid_cleaning_info'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['prepaid_cleaning_info']['placeholder'],
                        'required' => true,
                        'id' => 'prepaid_cleaning_info',
                    ]) !!}
            </div>

            @error('prepaid_cleaning_info')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Damage Waiver Protection</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div>
            <label for="damage_waiver_percentage" class="block text-sm font-medium text-gray-700 mb-1">
                Damage Waiver (%)
            </label>

            <div class="flex items-center flex-wrap gap-2 relative">
                {!! html()->input(
                        'number',
                        'damage_waiver_percentage',
                        $settings['Product Settings']['damage_waiver_percentage']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('damage_waiver_percentage'),
                        'border-red-500' => $errors->has('damage_waiver_percentage'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['damage_waiver_percentage']['placeholder'],
                        'required' => true,
                        'id' => 'damage_waiver_percentage',
                    ]) !!}

                <!-- Tooltip trigger -->
                <div class="relative group">
                    <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center cursor-pointer">
                        <x-heroicon-o-information-circle class="w-5 h-5" />
                    </span>

                    <!-- Tooltip -->
                    <div
                        class="tooltip-panel">
                        <p>Set the <strong>Damage Waiver (%)</strong> to auto-calculate the waiver charge.</p>
                        <br>
                        <strong>Formula:</strong> Charge = <strong>Rental Rate × Damage Waiver %.</strong>
                        <br><br>
                        <strong>Example:</strong> Rental Rate <strong>$400</strong> × Waiver
                        <strong>15% = $60</strong> damage waiver.
                    </div>
                </div>
            </div>

            @error('damage_waiver_percentage')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="damage_waiver_decline_label" class="block text-sm font-medium text-gray-700 mb-1">
                Decline Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'damage_waiver_decline_label',
                        old(
                            'damage_waiver_decline_label',
                            $settings['Product Settings']['damage_waiver_decline_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('damage_waiver_decline_label'),
                        'border-red-500' => $errors->has('damage_waiver_decline_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'damage_waiver_decline_label',
                    ])->required() !!}
            </div>

            @error('damage_waiver_decline_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="damage_waiver_approve_label" class="block text-sm font-medium text-gray-700 mb-1">
                Accept Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'damage_waiver_approve_label',
                        old(
                            'damage_waiver_approve_label',
                            $settings['Product Settings']['damage_waiver_approve_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('damage_waiver_approve_label'),
                        'border-red-500' => $errors->has('damage_waiver_approve_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'damage_waiver_approve_label',
                    ])->required() !!}
            </div>

            @error('damage_waiver_percentage')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-3">
            <label for="damage_waiver_info" class="block text-sm font-medium text-gray-700 mb-1">
                Damage Waiver Message
            </label>

            <div class="relative">
                {!! html()->textarea('damage_waiver_info', $settings['Product Settings']['damage_waiver_info']['setting_value'])->class([
                        'tinymce w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('damage_waiver_info'),
                        'border-red-500' => $errors->has('damage_waiver_info'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['damage_waiver_info']['placeholder'],
                        'required' => true,
                        'id' => 'damage_waiver_info',
                        'rows' => 3,
                    ]) !!}
            </div>

            @error('damage_waiver_info')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Thrown Track Insurance</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="track_insurance_decline_label" class="block text-sm font-medium text-gray-700 mb-1">
                Decline Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'track_insurance_decline_label',
                        old(
                            'track_insurance_decline_label',
                            $settings['Product Settings']['track_insurance_decline_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('track_insurance_decline_label'),
                        'border-red-500' => $errors->has('track_insurance_decline_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'track_insurance_decline_label',
                    ])->required() !!}
            </div>

            @error('track_insurance_decline_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="track_insurance_approve_label" class="block text-sm font-medium text-gray-700 mb-1">
                Accept Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'track_insurance_approve_label',
                        old(
                            'track_insurance_approve_label',
                            $settings['Product Settings']['track_insurance_approve_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('track_insurance_approve_label'),
                        'border-red-500' => $errors->has('track_insurance_approve_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter damage waiver decline label',
                        'autocomplete' => 'off',
                        'id' => 'track_insurance_approve_label',
                    ])->required() !!}
            </div>

            @error('track_insurance_approve_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="track_insurance_info" class="block text-sm font-medium text-gray-700 mb-1">
                Track Insurance Message
            </label>

            <div class="relative">
                {!! html()->textarea('track_insurance_info', $settings['Product Settings']['track_insurance_info']['setting_value'])->class([
                        'tinymce w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('track_insurance_info'),
                        'border-red-500' => $errors->has('track_insurance_info'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['track_insurance_info']['placeholder'],
                        'required' => true,
                        'id' => 'track_insurance_info',
                        'rows' => 3,
                    ]) !!}
            </div>

            @error('track_insurance_info')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Delivery Fees</h3>
    </div>

    @php
        // Exact keys you listed → pretty labels for the card heading
        $sizes = [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'x_large' => 'X-Large',
            '2x_large' => '2X Large',
            'commercial' => 'Commercial',
        ];

        // Period key → label (these appear in the field names)
        $periods = [
            'standard' => 'Standard',
            'extended' => 'Extended',
        ];
    @endphp

    @foreach ($sizes as $sizeKey => $sizeLabel)
        <div class="mb-6">
            <h4 class="text-md font-semibold text-gray-800 border-b border-gray-200 my-2">{{ $sizeLabel }}</h4>

            <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0">
                @foreach ($periods as $periodKey => $periodLabel)
                    @php
                        // Build the exact field name you have in settings/validation
                        // e.g. small_standard_delivery_fee, x_large_extended_delivery_fee, etc.
                        $name = "{$sizeKey}_{$periodKey}_delivery_fee";
                        $id = $name;
                        $hasError = $errors->has($name);
                        $value = old($name, data_get($settings, "Product Settings.$name.setting_value"));
                        $placeholder = data_get($settings, "Product Settings.$name.placeholder");
                    @endphp

                    <div>
                        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $periodLabel }}
                        </label>

                        <div class="relative">
                            {!! html()->input('number', $name, $value)->class([
                                    'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                                    'border-gray-300' => !$hasError,
                                    'border-red-500' => $hasError,
                                ])->attributes([
                                    'data-parsley-type' => 'number',
                                    'autocomplete' => 'off',
                                    'placeholder' => $placeholder,
                                    'required' => true,
                                    'id' => $id,
                                ]) !!}
                        </div>

                        @error($name)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</div>


<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-currency-dollar class="w-5 h-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Track Insurance Fees</h3>
    </div>

    @php
        // Exact keys you listed → pretty labels for the card heading
        $sizes = [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'x_large' => 'X-Large',
            '2x_large' => '2X Large',
            'commercial' => 'Commercial',
        ];

        // Period key → label (these appear in the field names)
        $periods = [
            'daily' => 'Daily',
            'weekend' => 'Weekend',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
        ];
    @endphp

    @foreach ($sizes as $sizeKey => $sizeLabel)
        <div class="mb-6">
            <h4 class="text-md font-semibold text-gray-800 border-b border-gray-200 my-2">{{ $sizeLabel }}</h4>

            <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0">
                @foreach ($periods as $periodKey => $periodLabel)
                    @php
                        // Build the exact field name you have in settings/validation
                        // e.g. small_daily_track_insurance_fee, x_large_weekly_track_insurance_fee, etc.
                        $name = "{$sizeKey}_{$periodKey}_track_insurance_fee";
                        $id = $name;
                        $hasError = $errors->has($name);
                        $value = old($name, data_get($settings, "Product Settings.$name.setting_value"));
                        $placeholder = data_get($settings, "Product Settings.$name.placeholder");
                    @endphp

                    <div>
                        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $periodLabel }}
                        </label>

                        <div class="relative">
                            {!! html()->input('number', $name, $value)->class([
                                    'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                                    'border-gray-300' => !$hasError,
                                    'border-red-500' => $hasError,
                                ])->attributes([
                                    'data-parsley-type' => 'number',
                                    'autocomplete' => 'off',
                                    'placeholder' => $placeholder,
                                    'required' => true,
                                    'id' => $id,
                                ]) !!}
                        </div>

                        @error($name)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</div>

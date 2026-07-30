@push('css')
    <style>
        .tox-tinymce {
            max-height: 15rem;
        }
    </style>
@endpush
<div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-truck class="w-5 h-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Delivery Range &amp; Delivery Fees</h3>
    </div>

    @php
        // Six administrative delivery tiers. "Custom 1–4" names are admin-only;
        // customers eventually see only the configured distance values.
        $deliveryTiers = [
            'standard' => 'Standard',
            'extended' => 'Extended',
            'custom_1' => 'Custom 1',
            'custom_2' => 'Custom 2',
            'custom_3' => 'Custom 3',
            'custom_4' => 'Custom 4',
        ];
        $deliverySizes = [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'x_large' => 'X-Large',
            '2x_large' => '2X Large',
            'commercial' => 'Commercial',
        ];
        $distanceUnitValue = data_get($settings, 'Product Settings.distance_unit.setting_value');
    @endphp

    {{-- Delivery range configuration --}}
    <div class="flex flex-wrap items-end gap-6">
        {{-- Standard Delivery --}}
        <div class="flex flex-col">
            <label for="standard_delivery_range" class="block text-sm font-medium text-gray-700 mb-1">
                Standard Delivery
            </label>
            {!! html()->input(
                    'number',
                    'standard_delivery_range',
                    $settings['Product Settings']['standard_delivery_range']['setting_value'],
                )->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                    'border-gray-300' => !$errors->has('standard_delivery_range'),
                    'border-red-500' => $errors->has('standard_delivery_range'),
                ])->attributes([
                    'data-parsley-type' => 'number',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Product Settings']['standard_delivery_range']['placeholder'],
                    'required' => true,
                    'id' => 'standard_delivery_range',
                ]) !!}
            @error('standard_delivery_range')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Extended Delivery --}}
        <div class="flex flex-col">
            <label for="extended_delivery_range" class="block text-sm font-medium text-gray-700 mb-1">
                Extended Delivery
            </label>
            {!! html()->input(
                    'number',
                    'extended_delivery_range',
                    $settings['Product Settings']['extended_delivery_range']['setting_value'],
                )->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                    'border-gray-300' => !$errors->has('extended_delivery_range'),
                    'border-red-500' => $errors->has('extended_delivery_range'),
                ])->attributes([
                    'data-parsley-type' => 'number',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Product Settings']['extended_delivery_range']['placeholder'],
                    'required' => true,
                    'id' => 'extended_delivery_range',
                ]) !!}
            @error('extended_delivery_range')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Extended Range checkbox --}}
        <div class="flex flex-col">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Extended Range
            </label>
            <label for="include_extended_range" class="inline-flex items-center gap-2 cursor-pointer py-2">
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
                <span class="text-sm text-gray-700">Include Option</span>
            </label>
            @error('include_extended_range')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Custom 1–4 distances (blank = tier not configured) --}}
        @foreach (['custom_1', 'custom_2', 'custom_3', 'custom_4'] as $customTier)
            @php
                $rangeName = "{$customTier}_delivery_range";
            @endphp
            <div class="flex flex-col">
                <label for="{{ $rangeName }}" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $deliveryTiers[$customTier] }}
                </label>
                {!! html()->input(
                        'number',
                        $rangeName,
                        old($rangeName, data_get($settings, "Product Settings.$rangeName.setting_value")),
                    )->class([
                        'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has($rangeName),
                        'border-red-500' => $errors->has($rangeName),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'min' => '0',
                        'placeholder' => data_get($settings, "Product Settings.$rangeName.placeholder"),
                        'id' => $rangeName,
                    ]) !!}
                @error($rangeName)
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        {{-- Distance Unit --}}
        <div class="flex flex-col">
            <label for="distance_unit" class="block text-sm font-medium text-gray-700 mb-1">
                Distance Unit
            </label>
            {!! html()->select(
                    'distance_unit',
                    [
                        'Kilometers' => 'Kilometers',
                        'Miles' => 'Miles',
                    ],
                    $settings['Product Settings']['distance_unit']['setting_value'],
                )->class([
                    'w-32 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                    'border-gray-300' => !$errors->has('distance_unit'),
                    'border-red-500' => $errors->has('distance_unit'),
                ])->attributes([
                    'id' => 'distance_unit',
                    'required' => true,
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Product Settings']['distance_unit']['placeholder'] ?? '',
                ]) !!}
            @error('distance_unit')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p class="mt-2 text-xs text-gray-500">
        Custom 1&ndash;4 names are administrative only &mdash; customers see just the configured distance.
        Leave a distance blank to keep that tier unconfigured. The Distance Unit applies to all six tiers.
    </p>

    {{-- Delivery fee matrix (one-way rates) --}}
    <div class="mt-6">
        <h4 class="text-md font-semibold text-gray-800 border-b border-gray-200 pb-2 mb-1">Delivery Fees</h4>
        <p class="text-xs text-gray-500 mb-3">
            One-way rates &mdash; the amount charged for one direction of service.
            Blank = rate not configured; 0.00 = intentionally free.
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 pr-4 font-medium text-gray-700 whitespace-nowrap">Equipment Size</th>
                        @foreach ($deliveryTiers as $tierKey => $tierLabel)
                            @php
                                $tierRange = data_get($settings, "Product Settings.{$tierKey}_delivery_range.setting_value");
                            @endphp
                            <th class="text-left py-2 pr-4 font-medium text-gray-700 whitespace-nowrap">
                                {{ $tierLabel }}
                                <span class="block text-xs font-normal text-gray-400">
                                    {{ $tierRange !== null && $tierRange !== '' ? $tierRange . ' ' . $distanceUnitValue : '—' }}
                                </span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deliverySizes as $sizeKey => $sizeLabel)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 pr-4 font-medium text-gray-800 whitespace-nowrap">{{ $sizeLabel }}</td>
                            @foreach ($deliveryTiers as $tierKey => $tierLabel)
                                @php
                                    $name = "{$sizeKey}_{$tierKey}_delivery_fee";
                                    $hasError = $errors->has($name);
                                    $value = old($name, data_get($settings, "Product Settings.$name.setting_value"));
                                    $placeholder = data_get($settings, "Product Settings.$name.placeholder");
                                    $isCustomTier = str_starts_with($tierKey, 'custom_');
                                @endphp
                                <td class="py-2 pr-4">
                                    {!! html()->input('number', $name, $value)->class([
                                            'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                                            'border-gray-300' => !$hasError,
                                            'border-red-500' => $hasError,
                                        ])->attributes(array_filter([
                                            'data-parsley-type' => 'number',
                                            'autocomplete' => 'off',
                                            'step' => '0.01',
                                            'min' => '0',
                                            'placeholder' => $placeholder,
                                            'required' => $isCustomTier ? null : true,
                                            'id' => $name,
                                            'aria-label' => "$sizeLabel $tierLabel delivery fee",
                                        ], fn ($attr) => $attr !== null)) !!}

                                    @error($name)
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Credit Card Processing Fee moved to Tax Settings & Credit Card Rate --}}
</div>

@include('admin.configurations.partials._allocated_settings')

@include('admin.configurations.partials._tax_settings')

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
        <x-heroicon-o-sparkles class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Product Cleaning</h3>
    </div>

    <p class="mb-3 text-xs text-gray-500">
        Multipliers applied to each Prepaid Cleaning Rate's amount (rounded up to the next dollar) to get the
        Std/Moderate/Extreme clean fee shown below.
    </p>

    <div class="flex flex-wrap items-end gap-6">
        @foreach ([
            'std_clean_req' => 'Std Clean Multiplier',
            'moderate_clean_req' => 'Moderate Clean Multiplier',
            'extreme_clean_req' => 'Extreme Clean Multiplier',
        ] as $cleanReqName => $cleanReqLabel)
            <div class="flex flex-col">
                <label for="{{ $cleanReqName }}" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $cleanReqLabel }}
                </label>

                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">*</span>
                    {!! html()->input(
                            'number',
                            $cleanReqName,
                            old($cleanReqName, data_get($settings, "Product Settings.$cleanReqName.setting_value")),
                        )->class([
                            'w-24 pl-7 pr-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                            'border-gray-300' => !$errors->has($cleanReqName),
                            'border-red-500' => $errors->has($cleanReqName),
                        ])->attributes([
                            'data-parsley-type' => 'number',
                            'autocomplete' => 'off',
                            'step' => '0.01',
                            'min' => '0',
                            'placeholder' => data_get($settings, "Product Settings.$cleanReqName.placeholder"),
                            'id' => $cleanReqName,
                        ]) !!}
                </div>

                @error($cleanReqName)
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
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

{{-- Delivery Fees consolidated into the "Delivery Range & Delivery Fees" card above --}}

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

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Tire Insurance</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="tire_insurance_decline_label" class="block text-sm font-medium text-gray-700 mb-1">
                Decline Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'tire_insurance_decline_label',
                        old(
                            'tire_insurance_decline_label',
                            $settings['Product Settings']['tire_insurance_decline_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('tire_insurance_decline_label'),
                        'border-red-500' => $errors->has('tire_insurance_decline_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter tire insurance decline label',
                        'autocomplete' => 'off',
                        'id' => 'tire_insurance_decline_label',
                    ]) !!}
            </div>

            @error('tire_insurance_decline_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tire_insurance_approve_label" class="block text-sm font-medium text-gray-700 mb-1">
                Accept Button Label
            </label>

            <div class="relative">
                {!! html()->text(
                        'tire_insurance_approve_label',
                        old(
                            'tire_insurance_approve_label',
                            $settings['Product Settings']['tire_insurance_approve_label']['setting_value'] ?? null,
                        ),
                    )->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('tire_insurance_approve_label'),
                        'border-red-500' => $errors->has('tire_insurance_approve_label'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter tire insurance accept label',
                        'autocomplete' => 'off',
                        'id' => 'tire_insurance_approve_label',
                    ]) !!}
            </div>

            @error('tire_insurance_approve_label')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="tire_insurance_info" class="block text-sm font-medium text-gray-700 mb-1">
                Tire Insurance Message
            </label>

            <div class="relative">
                {!! html()->textarea('tire_insurance_info', $settings['Product Settings']['tire_insurance_info']['setting_value'] ?? null)->class([
                        'tinymce w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('tire_insurance_info'),
                        'border-red-500' => $errors->has('tire_insurance_info'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['tire_insurance_info']['placeholder'] ?? 'Enter message for tire insurance popup...',
                        'id' => 'tire_insurance_info',
                        'rows' => 3,
                    ]) !!}
            </div>

            @error('tire_insurance_info')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-currency-dollar class="w-5 h-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Tire Insurance Fees</h3>
    </div>

    @php
        $sizes = [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'x_large' => 'X-Large',
            '2x_large' => '2X Large',
            'commercial' => 'Commercial',
        ];

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
                        $name = "{$sizeKey}_{$periodKey}_tire_insurance_fee";
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
                                    'placeholder' => $placeholder ?? '0',
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

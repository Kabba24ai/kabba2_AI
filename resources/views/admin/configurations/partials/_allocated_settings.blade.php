{{-- Allocated Hours Settings --}}
{{-- Price Rate Multiplier & Allocated Hours Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-5">
        <x-heroicon-o-currency-dollar class="h-5 w-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">
            Price Rate Multiplier & Allocated Hours Settings
        </h3>
    </div>

    {{-- Row 1: Price Rate Multipliers --}}
    <div class="flex flex-wrap items-end gap-4 mb-7">

        {{-- Daily — Base Rate (visual only) --}}
        <div class="flex flex-col">
            <label class="block text-sm font-medium text-gray-700 mb-1">Daily</label>
                <input type="text" disabled value="Base Rate"
                    class="w-24 px-3 py-2 text-sm text-center font-medium border border-gray-200 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed">
        </div>

        {{-- Weekend multiplier --}}
        <div class="flex flex-col">
            <div class="flex items-center justify-between mb-1">
                <label for="weekend_multiplier" class="block text-sm font-medium text-gray-700">Weekend-X</label>
            </div>
            {!! html()->input('number', 'weekend_multiplier', $settings['Price Rate Multiplier Settings']['weekend_multiplier']['setting_value'] ?? '')->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('weekend_multiplier'),
                    'border-red-500' => $errors->has('weekend_multiplier'),
                ])->attributes([
                    'step' => '0.01',
                    'min' => '0',
                    'autocomplete' => 'off',
                    'id' => 'weekend_multiplier',
                    'placeholder' => '1.5',
                ]) !!}
            @error('weekend_multiplier')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Weekly multiplier --}}
        <div class="flex flex-col">
            <div class="flex items-center justify-between mb-1">
                <label for="weekly_multiplier" class="block text-sm font-medium text-gray-700">Weekly-X</label>
            </div>
            {!! html()->input('number', 'weekly_multiplier', $settings['Price Rate Multiplier Settings']['weekly_multiplier']['setting_value'] ?? '')->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('weekly_multiplier'),
                    'border-red-500' => $errors->has('weekly_multiplier'),
                ])->attributes([
                    'step' => '0.01',
                    'min' => '0',
                    'autocomplete' => 'off',
                    'id' => 'weekly_multiplier',
                    'placeholder' => '4.0',
                ]) !!}
            @error('weekly_multiplier')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Monthly multiplier --}}
        <div class="flex flex-col">
            <div class="flex items-center justify-between mb-1">
                <label for="monthly_multiplier" class="block text-sm font-medium text-gray-700">Monthly-X</label>
            </div>
            {!! html()->input('number', 'monthly_multiplier', $settings['Price Rate Multiplier Settings']['monthly_multiplier']['setting_value'] ?? '')->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('monthly_multiplier'),
                    'border-red-500' => $errors->has('monthly_multiplier'),
                ])->attributes([
                    'step' => '0.01',
                    'min' => '0',
                    'autocomplete' => 'off',
                    'id' => 'monthly_multiplier',
                    'placeholder' => '16.0',
                ]) !!}
            @error('monthly_multiplier')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Auto Calculation info tooltip --}}
        <div class="flex flex-col justify-end pb-2">
            <div class="relative group">
                <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center gap-1 cursor-pointer whitespace-nowrap">
                    <x-heroicon-o-information-circle class="w-5 h-5" />
                    Auto Calculation
                </span>
                <div class="tooltip-panel tooltip-panel--right max-w-sm">
                    Automatically calculates Weekend, Weekly, and Monthly prices from the base price using this
                    multiplier. You can manually override any calculated price at any time.
                </div>
            </div>
        </div>
    </div>

    {{-- Row 1b: Smart Price Rounding --}}
    @php
        $configuredPriceEndings = \App\Helpers\RentalPriceHelper::parseEndings(
            $settings['Price Rate Multiplier Settings']['allowed_price_endings']['setting_value'] ?? null,
        );
    @endphp
    <div class="mb-7">
        <div class="flex items-center gap-2 mb-2">
            <label class="block text-sm font-medium text-gray-700">Allowed Price Endings</label>
            <div class="relative group">
                <span class="text-blue-400 hover:text-blue-500 flex items-center cursor-pointer">
                    <x-heroicon-o-information-circle class="w-5 h-5" />
                </span>
                <div class="tooltip-panel max-w-sm">
                    Calculated Weekend, Weekly, and Monthly prices round upward to the nearest
                    whole-dollar amount ending in one of these digits. A price stays in the
                    previous hundred until the calculated amount is at least the Hundred-Entry
                    Threshold into the new hundred (e.g. with a $10 threshold, $409.99 stays at
                    $397 while $410.00 becomes $414). Clear all endings to turn smart rounding
                    off. Applies to rental products only.
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-end gap-4">
            @foreach ([0, 1, 2] as $endingSlot)
                <div class="flex flex-col">
                    {!! html()->input('number', "price_endings[{$endingSlot}]", old("price_endings.{$endingSlot}", $configuredPriceEndings[$endingSlot] ?? ''))->class([
                            'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has("price_endings.{$endingSlot}"),
                            'border-red-500' => $errors->has("price_endings.{$endingSlot}"),
                        ])->attributes([
                            'min' => '0',
                            'max' => '9',
                            'step' => '1',
                            'autocomplete' => 'off',
                            'id' => "price_ending_{$endingSlot}",
                            'placeholder' => '0–9',
                        ]) !!}
                </div>
            @endforeach

            <div class="flex flex-col">
                <label for="hundred_entry_threshold" class="block text-sm font-medium text-gray-700 mb-1">
                    Hundred-Entry Threshold ($)
                </label>
                {!! html()->input('number', 'hundred_entry_threshold', old('hundred_entry_threshold', $settings['Price Rate Multiplier Settings']['hundred_entry_threshold']['setting_value'] ?? ''))->class([
                        'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                        'border-gray-300' => !$errors->has('hundred_entry_threshold'),
                        'border-red-500' => $errors->has('hundred_entry_threshold'),
                    ])->attributes([
                        'min' => '0',
                        'step' => '1',
                        'autocomplete' => 'off',
                        'id' => 'hundred_entry_threshold',
                    ]) !!}
            </div>

            <span class="text-xs text-gray-500 pb-3">*Whole $ amounts only</span>
        </div>
        @foreach (['price_endings', 'price_endings.0', 'price_endings.1', 'price_endings.2', 'hundred_entry_threshold'] as $roundingField)
            @error($roundingField)
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        @endforeach
    </div>

    {{-- Row 2: Allocated Hours --}}
    <div class="flex flex-wrap items-end gap-4">

        {{-- Daily Hrs --}}
        <div class="flex flex-col">
            <label for="daily_hours" class="block text-sm font-medium text-gray-700 mb-1">Daily Hrs</label>
            {!! html()->input('number', 'daily_hours', $settings['Allocated Hours Settings']['daily_hours']['setting_value'])->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('daily_hours'),
                    'border-red-500' => $errors->has('daily_hours'),
                ])->attributes([
                    'data-parsley-type' => 'number',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Allocated Hours Settings']['daily_hours']['placeholder'],
                    'required' => true,
                    'id' => 'daily_hours',
                ]) !!}
            @error('daily_hours')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Weekend Hrs --}}
        <div class="flex flex-col">
            <label for="weekend_hours" class="block text-sm font-medium text-gray-700 mb-1">Weekend Hrs</label>
            {!! html()->input('number', 'weekend_hours', $settings['Allocated Hours Settings']['weekend_hours']['setting_value'])->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('weekend_hours'),
                    'border-red-500' => $errors->has('weekend_hours'),
                ])->attributes([
                    'data-parsley-type' => 'number',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Allocated Hours Settings']['weekend_hours']['placeholder'],
                    'required' => true,
                    'id' => 'weekend_hours',
                ]) !!}
            @error('weekend_hours')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Weekly Hrs --}}
        <div class="flex flex-col">
            <label for="weekly_hours" class="block text-sm font-medium text-gray-700 mb-1">Weekly Hrs</label>
            {!! html()->input('number', 'weekly_hours', $settings['Allocated Hours Settings']['weekly_hours']['setting_value'])->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('weekly_hours'),
                    'border-red-500' => $errors->has('weekly_hours'),
                ])->attributes([
                    'data-parsley-type' => 'number',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Allocated Hours Settings']['weekly_hours']['placeholder'],
                    'required' => true,
                    'id' => 'weekly_hours',
                ]) !!}
            @error('weekly_hours')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Monthly Hrs --}}
        <div class="flex flex-col">
            <label for="monthly_hours" class="block text-sm font-medium text-gray-700 mb-1">Monthly Hrs</label>
            {!! html()->input('number', 'monthly_hours', $settings['Allocated Hours Settings']['monthly_hours']['setting_value'])->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('monthly_hours'),
                    'border-red-500' => $errors->has('monthly_hours'),
                ])->attributes([
                    'data-parsley-type' => 'number',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Allocated Hours Settings']['monthly_hours']['placeholder'],
                    'required' => true,
                    'id' => 'monthly_hours',
                ]) !!}
            @error('monthly_hours')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Overage Rate (%) --}}
        {{-- <div class="flex flex-col">
            <label for="overage_rate_percentage" class="block text-sm font-medium text-gray-700 mb-1">
                Overage Rate (%)
            </label>
            {!! html()->input('number', 'overage_rate_percentage', $settings['Product Settings']['overage_rate_percentage']['setting_value'])->class([
                    'w-24 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                    'border-gray-300' => !$errors->has('overage_rate_percentage'),
                    'border-red-500' => $errors->has('overage_rate_percentage'),
                ])->attributes([
                    'step' => '0.1',
                    'min' => '0',
                    'autocomplete' => 'off',
                    'placeholder' => $settings['Product Settings']['overage_rate_percentage']['placeholder'] ?? '16.7',
                    'id' => 'overage_rate_percentage',
                ]) !!}
            @error('overage_rate_percentage')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div> --}}

        {{-- Calculation info tooltip --}}
        {{-- <div class="flex flex-col justify-end pb-2">
            <div class="relative group">
                <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center gap-1 cursor-pointer whitespace-nowrap">
                    <x-heroicon-o-information-circle class="w-5 h-5" />
                    Calculation
                </span>
                <div class="tooltip-panel tooltip-panel--right">
                    <strong>Overage Rate</strong> is a percentage used to compute the
                    <strong>hourly overage fee</strong> for this product.<br><br>
                    <strong>Formula:</strong> Hourly Overage = <strong>Daily Rental Rate × Overage Rate (%)</strong><br>
                    If a renter exceeds their allocated hours (e.g., 8/day, 14/weekend, 40/week, 160/month),
                    this fee applies <strong>for each hour over.</strong><br><br>
                    <strong>Example:</strong> Daily Rate <strong>$300</strong> × Overage Rate
                    <strong>10% = $30/hour</strong> over the limit.<br><br>
                    <strong>Note:</strong> Partial hours are billed as a full hour.
                </div>
            </div>
        </div> --}}
    </div>
</div>

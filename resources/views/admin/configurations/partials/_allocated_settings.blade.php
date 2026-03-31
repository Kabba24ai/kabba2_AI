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
                <label for="weekend_multiplier" class="block text-sm font-medium text-gray-700">Weekend</label>
                <button type="button"
                    onclick="document.getElementById('weekend_multiplier').value=''"
                    class="ml-2 text-gray-400 hover:text-red-500 transition-colors" title="Clear">
                    <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                </button>
            </div>
            {!! html()->input('number', 'weekend_multiplier', $settings['Allocated Hours Settings']['weekend_multiplier']['setting_value'] ?? '')->class([
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
                <label for="weekly_multiplier" class="block text-sm font-medium text-gray-700">Weekly</label>
                <button type="button"
                    onclick="document.getElementById('weekly_multiplier').value=''"
                    class="ml-2 text-gray-400 hover:text-red-500 transition-colors" title="Clear">
                    <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                </button>
            </div>
            {!! html()->input('number', 'weekly_multiplier', $settings['Allocated Hours Settings']['weekly_multiplier']['setting_value'] ?? '')->class([
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
                <label for="monthly_multiplier" class="block text-sm font-medium text-gray-700">Monthly</label>
                <button type="button"
                    onclick="document.getElementById('monthly_multiplier').value=''"
                    class="ml-2 text-gray-400 hover:text-red-500 transition-colors" title="Clear">
                    <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                </button>
            </div>
            {!! html()->input('number', 'monthly_multiplier', $settings['Allocated Hours Settings']['monthly_multiplier']['setting_value'] ?? '')->class([
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
        <div class="flex flex-col">
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
        </div>

        {{-- Calculation info tooltip --}}
        <div class="flex flex-col justify-end pb-2">
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
        </div>
    </div>
</div>

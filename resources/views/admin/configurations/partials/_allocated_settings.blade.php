{{-- Allocated Hours Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-clock class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Allocated Hours Settings</h3>
    </div>

    <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0 mb-6">
        <div>
            <label for="daily_hours" class="block text-sm font-medium text-gray-700 mb-1">
                Daily Hours
            </label>

            <div class="relative">
                {!! html()->input('number', 'daily_hours', $settings['Allocated Hours Settings']['daily_hours']['setting_value'])->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('daily_hours'),
                        'border-red-500' => $errors->has('daily_hours'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Allocated Hours Settings']['daily_hours']['placeholder'],
                        'required' => true,
                        'id' => 'daily_hours',
                    ]) !!}
            </div>

            @error('daily_hours')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="weekend_hours" class="block text-sm font-medium text-gray-700 mb-1">
                Weekend Hours
            </label>

            <div class="relative">
                {!! html()->input('number', 'weekend_hours', $settings['Allocated Hours Settings']['weekend_hours']['setting_value'])->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('weekend_hours'),
                        'border-red-500' => $errors->has('weekend_hours'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Allocated Hours Settings']['weekend_hours']['placeholder'],
                        'required' => true,
                        'id' => 'weekend_hours',
                    ]) !!}
            </div>

            @error('weekend_hours')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="weekly_hours" class="block text-sm font-medium text-gray-700 mb-1">
                Weekly Hours
            </label>

            <div class="relative">
                {!! html()->input('number', 'weekly_hours', $settings['Allocated Hours Settings']['weekly_hours']['setting_value'])->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('weekly_hours'),
                        'border-red-500' => $errors->has('weekly_hours'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Allocated Hours Settings']['weekly_hours']['placeholder'],
                        'required' => true,
                        'id' => 'weekly_hours',
                    ]) !!}
            </div>

            @error('weekly_hours')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="monthly_hours" class="block text-sm font-medium text-gray-700 mb-1">
                Monthly Hours
            </label>

            <div class="relative">
                {!! html()->input('number', 'monthly_hours', $settings['Allocated Hours Settings']['monthly_hours']['setting_value'])->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('monthly_hours'),
                        'border-red-500' => $errors->has('monthly_hours'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Allocated Hours Settings']['monthly_hours']['placeholder'],
                        'required' => true,
                        'id' => 'monthly_hours',
                    ]) !!}
            </div>

            @error('monthly_hours')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

    </div>

    <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0 mb-6">
        <div>
            <label for="overage_rate_percentage" class="block text-sm font-medium text-gray-700 mb-1">
                Overage Rate (%)
            </label>

            <div class="flex items-center flex-wrap gap-2">
                {!! html()->input(
                        'number',
                        'overage_rate_percentage',
                        $settings['Product Settings']['overage_rate_percentage']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('overage_rate_percentage'),
                        'border-red-500' => $errors->has('overage_rate_percentage'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Product Settings']['overage_rate_percentage']['placeholder'],
                        'required' => true,
                        'id' => 'overage_rate_percentage',
                    ]) !!}
                <div class="relative overflow-visible"> <!-- important: prevent clipping -->
                    <div class="flex items-center gap-1">
                        <label
                            class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span class="relative group">
                                <span
                                    class="text-blue-400 hover:text-blue-500 text-sm flex items-center gap-1 cursor-pointer">
                                    <x-heroicon-o-information-circle class="w-5 h-5" />
                                    Overage Calculation
                                </span>

                                <!-- Tooltip -->
                                <div
                                    class="tooltip-panel">
                                    <strong>Overage Rate</strong> Rate is a percentage used to compute the
                                    <strong>hourly overage
                                        fee</strong> for this product.
                                    <br><br>
                                    <strong>
                                        Formula:</strong> Hourly Overage = <strong>Daily Rental Rate × Overage Rate
                                        (%).
                                    </strong>
                                    <br>
                                    If a renter exceeds their allocated hours (e.g., 8/day, 14/weekend, 40/week,
                                    160/month), this
                                    fee applies <strong>for each hour over.</strong>
                                    <br><br>
                                    <strong>Example:</strong> Daily Rate <strong>$300</strong> × Overage Rate
                                    <strong>10% =
                                        $30/hour</strong> over the limit.
                                    <br><br>
                                    <strong>Note:</strong> Partial hours are billed as a full hour.
                                </div>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            @error('overage_rate_percentage')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

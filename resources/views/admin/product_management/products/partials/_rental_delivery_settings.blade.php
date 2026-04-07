<div class="grid grid-cols-1 lg:grid-cols-2 gap-1">
    <!-- Rental Configuration -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Rental Pricing</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Daily -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Daily</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_daily')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                        <p class="text-[10px] leading-tight text-gray-500 mt-1">{{ $allocatedHoursSettings['daily_hours'] ?? 8 }} Hrs Allocated</p>
                <div id="rental-daily-errors"></div>
            </div>

            <!-- Weekend Spcl. -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Weekend
                    Spcl.</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_weekend')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-[10px] leading-tight text-gray-500 mt-1">
                    {{ $allocatedHoursSettings['weekend_hours'] ?? 14 }} Hrs Allocated -
                    {{ $priceRateMultiplierSettings['weekend_multiplier'] ?? '1.5' }} Daily Rate
                </p>
                <div id="rental-weekend-errors"></div>
            </div>

            <!-- Weekly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Weekly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_weekly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-[10px] leading-tight text-gray-500 mt-1">
                    {{ $allocatedHoursSettings['weekly_hours'] ?? 40 }} Hrs Allocated -
                    {{ $priceRateMultiplierSettings['weekly_multiplier'] ?? '4.0' }} Daily Rate
                </p>
                <div id="rental-weekly-errors"></div>
            </div>

            <!-- Monthly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Monthly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_monthly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-[10px] leading-tight text-gray-500 mt-1">
                    {{ $allocatedHoursSettings['monthly_hours'] ?? 160 }} Hrs Allocated -
                    {{ $priceRateMultiplierSettings['monthly_multiplier'] ?? '16.0' }} Daily Rate
                </p>
                <div id="rental-monthly-errors"></div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Damage Waiver</label>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Damage Waiver Daily -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_daily')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                    <div class="relative overflow-visible"> <!-- important: prevent clipping -->
                        <div class="flex items-center gap-1">
                            <label
                                class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <span class="relative group">
                                    <span
                                        class="text-blue-400 hover:text-blue-500 text-sm flex items-center gap-1 cursor-pointer">
                                        <x-heroicon-o-information-circle class="w-5 h-5" />
                                    </span>

                                    <!-- Tooltip -->
                                    <div class="tooltip-panel">
                                        <p>Set the <strong>Damage Waiver (%)</strong> to auto-calculate the waiver
                                            charge.</p>
                                        <br>
                                        <strong>Formula:</strong> Charge = <strong>Rental Rate × Damage Waiver
                                            %.</strong>
                                        <br><br>
                                        <strong>Example:</strong> Rental Rate <strong>$400</strong> × Waiver
                                        <strong>15% = $60</strong> damage waiver.
                                        <br><br>
                                        <strong>Note:</strong> Update the <strong>Damage Waiver (%)</strong> in Settings

                                    </div>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div id="rental-damage-waiver-daily-errors"></div>
            </div>

            <!-- Damage Waiver Weekend -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_weekend')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-damage-waiver-weekend-errors"></div>
            </div>

            <!-- Damage Waiver Weekly -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_weekly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-damage-waiver-weekly-errors"></div>
            </div>

            <!-- Damage Waiver Monthly -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_monthly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-damage-waiver-monthly-errors"></div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Track Insurance</label>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_track_insurance_daily')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-track-insurance-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-track-insurance-daily-errors"></div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_track_insurance_weekend')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-track-insurance-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-track-insurance-weekend-errors"></div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_track_insurance_weekly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-track-insurance-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-track-insurance-weekly-errors"></div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_track_insurance_monthly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-truck-insurance-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-truck-insurance-monthly-errors"></div>
            </div>
        </div>

        {{-- Sizes --}}
        <div class="mt-3">
            <div class="flex items-center flex-wrap gap-6">
                <label for="track_insurance_small"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'track_insurance_size_setting',
                            old('track_insurance_size_setting', $objProduct->track_insurance_size_setting ?? null) == 'Small',
                            'Small',
                        )->id('track_insurance_small')->class('mr-2')->attribute('data-parsley-errors-container', '#track-insurance-sizes-errors') !!}
                    Small
                </label>

                <label for="track_insurance_medium"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'track_insurance_size_setting',
                            old('track_insurance_size_setting', $objProduct->track_insurance_size_setting ?? null) == 'Medium',
                            'Medium',
                        )->id('track_insurance_medium')->class('mr-2')->attribute('data-parsley-errors-container', '#track-insurance-sizes-errors') !!}
                    Medium
                </label>

                <label for="track_insurance_large"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'track_insurance_size_setting',
                            old('track_insurance_size_setting', $objProduct->track_insurance_size_setting ?? null) == 'Large',
                            'Large',
                        )->id('track_insurance_large')->class('mr-2')->attribute('data-parsley-errors-container', '#track-insurance-sizes-errors') !!}
                    Large
                </label>

                <label for="track_insurance_xlarge"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'track_insurance_size_setting',
                            old('track_insurance_size_setting', $objProduct->track_insurance_size_setting ?? null) == 'X-Large',
                            'X-Large',
                        )->id('track_insurance_xlarge')->class('mr-2')->attribute('data-parsley-errors-container', '#track-insurance-sizes-errors') !!}
                    X-Large
                </label>

                <label for="track_insurance_2xlarge"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'track_insurance_size_setting',
                            old('track_insurance_size_setting', $objProduct->track_insurance_size_setting ?? null) == '2X-Large',
                            '2X-Large',
                        )->id('track_insurance_2xlarge')->class('mr-2')->attribute('data-parsley-errors-container', '#track-insurance-sizes-errors') !!}
                    2X-Large
                </label>

                <label for="track_insurance_commercial"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'track_insurance_size_setting',
                            old('track_insurance_size_setting', $objProduct->track_insurance_size_setting ?? null) == 'Commercial',
                            'Commercial',
                        )->id('track_insurance_commercial')->class('mr-2')->attribute('data-parsley-errors-container', '#track-insurance-sizes-errors') !!}
                    Commercial
                </label>
            </div>

            <div id="track-insurance-sizes-errors"></div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tire Insurance</label>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_tire_insurance_daily', $product->rental_tire_insurance_daily ?? null)->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-tire-insurance-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-tire-insurance-daily-errors"></div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_tire_insurance_weekend', $product->rental_tire_insurance_weekend ?? null)->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-tire-insurance-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-tire-insurance-weekend-errors"></div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_tire_insurance_weekly', $product->rental_tire_insurance_weekly ?? null)->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-tire-insurance-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-tire-insurance-weekly-errors"></div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_tire_insurance_monthly', $product->rental_tire_insurance_monthly ?? null)->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-tire-insurance-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-tire-insurance-monthly-errors"></div>
            </div>
        </div>

        {{-- Tire Insurance Sizes --}}
        <div class="mt-3">
            <div class="flex items-center flex-wrap gap-6">
                <label for="tire_insurance_small"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'tire_insurance_size_setting',
                            old('tire_insurance_size_setting', $objProduct->tire_insurance_size_setting ?? null) == 'Small',
                            'Small',
                        )->id('tire_insurance_small')->class('mr-2')->attribute('data-parsley-errors-container', '#tire-insurance-sizes-errors') !!}
                    Small
                </label>

                <label for="tire_insurance_medium"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'tire_insurance_size_setting',
                            old('tire_insurance_size_setting', $objProduct->tire_insurance_size_setting ?? null) == 'Medium',
                            'Medium',
                        )->id('tire_insurance_medium')->class('mr-2')->attribute('data-parsley-errors-container', '#tire-insurance-sizes-errors') !!}
                    Medium
                </label>

                <label for="tire_insurance_large"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'tire_insurance_size_setting',
                            old('tire_insurance_size_setting', $objProduct->tire_insurance_size_setting ?? null) == 'Large',
                            'Large',
                        )->id('tire_insurance_large')->class('mr-2')->attribute('data-parsley-errors-container', '#tire-insurance-sizes-errors') !!}
                    Large
                </label>

                <label for="tire_insurance_xlarge"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'tire_insurance_size_setting',
                            old('tire_insurance_size_setting', $objProduct->tire_insurance_size_setting ?? null) == 'X-Large',
                            'X-Large',
                        )->id('tire_insurance_xlarge')->class('mr-2')->attribute('data-parsley-errors-container', '#tire-insurance-sizes-errors') !!}
                    X-Large
                </label>

                <label for="tire_insurance_2xlarge"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'tire_insurance_size_setting',
                            old('tire_insurance_size_setting', $objProduct->tire_insurance_size_setting ?? null) == '2X-Large',
                            '2X-Large',
                        )->id('tire_insurance_2xlarge')->class('mr-2')->attribute('data-parsley-errors-container', '#tire-insurance-sizes-errors') !!}
                    2X-Large
                </label>

                <label for="tire_insurance_commercial"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'tire_insurance_size_setting',
                            old('tire_insurance_size_setting', $objProduct->tire_insurance_size_setting ?? null) == 'Commercial',
                            'Commercial',
                        )->id('tire_insurance_commercial')->class('mr-2')->attribute('data-parsley-errors-container', '#tire-insurance-sizes-errors') !!}
                    Commercial
                </label>
            </div>

            <div id="tire-insurance-sizes-errors"></div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Sale Prices -->
        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sale Price</label>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 ">
            <!-- Daily -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_daily')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#sale-price-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="sale-price-daily-errors"></div>
                @error('sale_price_daily')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Weekend Spcl. -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_weekend')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#sale-price-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="sale-price-weekend-errors"></div>
                @error('sale_price_weekend')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Weekly -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_weekly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#sale-price-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="sale-price-weekly-errors"></div>
                @error('sale_price_weekly')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Monthly -->
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_monthly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#sale-price-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="sale-price-monthly-errors"></div>
                @error('sale_price_monthly')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Related Products Price -->
        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Products Price</label>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 ">
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('related_product_price_daily')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#related-product-price-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="related-product-price-daily-errors"></div>
                @error('related_product_price_daily')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('related_product_price_weekend')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#related-product-price-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="related-product-price-weekend-errors"></div>
                @error('related_product_price_weekend')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('related_product_price_weekly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#related-product-price-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="related-product-price-weekly-errors"></div>
                @error('related_product_price_weekly')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('related_product_price_monthly')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#related-product-price-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="related-product-price-monthly-errors"></div>
                @error('related_product_price_monthly')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>
    </div>

    <!-- Additional Prices -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Additional Rental Prices</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Prepaid Cleaning -->
            <div>
                <label
                    class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid
                    Cleaning</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_prepaid_cleaning')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-prepaid-cleaning-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-prepaid-cleaning-errors"></div>
            </div>

            <!-- Prepaid Fuel -->
            <div>
                <label
                    class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid
                    Fuel</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_prepaid_fuel')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-prepaid-fuel-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-prepaid-fuel-errors"></div>
            </div>

            <div class="col-span-2">
            </div>

            {{-- <div>
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Overage Rate / Hr.
                </label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('hour_rate')->attributes([
                            'placeholder' => '0',
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#hour-rate-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="hour-rate-errors"></div>
                @error('hour_rate')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <div class="flex items-center pt-6">
                    <input id="hour_tracking" type="checkbox" name="hour_tracking" value="Yes"
                        {{ old('hour_tracking', $objProduct->hour_tracking ?? 'Yes') === 'Yes' ? 'checked' : '' }}
                        aria-describedby="hour-tracking-error"
                        class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700" />
                    <label for="hour_tracking" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Hour Tracking
                    </label>
                </div>
                @error('hour_tracking')
                    <span id="hour-tracking-error" class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror

            </div> --}}

            <div>
                <div class="flex items-center gap-1">
                    <select id="prepaid_cleaning_rate_setting" name="prepaid_cleaning_rate_setting"
                        class="w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white">
                        <option value="" disabled selected>Select Rate</option>
                        @foreach ($productSettings['prepaid_cleaning_rates'] ?? [] as $prepaidCleaningRate)
                            <option value="{{ $prepaidCleaningRate['description'] }}"
                                data-rate="{{ $prepaidCleaningRate['rate'] }}" @selected(old('prepaid_cleaning_rate_setting', $objProduct->prepaid_cleaning_rate_setting ?? '') === $prepaidCleaningRate['description'])>
                                {{ $prepaidCleaningRate['description'] }} - {{ $prepaidCleaningRate['rate'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-1">
                    <select id="prepaid_fuel_rate_setting" name="prepaid_fuel_rate_setting"
                        class="w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white">
                        <option value="" disabled selected>Select Rate</option>
                        @foreach ($productSettings['prepaid_fuel_rates'] ?? [] as $prepaidFuelRate)
                            <option value="{{ $prepaidFuelRate['description'] }}"
                                data-rate="{{ $prepaidFuelRate['rate'] }}" @selected(old('prepaid_fuel_rate_setting', $objProduct->prepaid_fuel_rate_setting ?? '') === $prepaidFuelRate['description'])>
                                {{ $prepaidFuelRate['description'] }} - {{ $prepaidFuelRate['rate'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- <div class="relative overflow-visible"> <!-- important: prevent clipping -->
                <div class="flex items-center gap-1">
                    <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <span class="relative group">
                            <span
                                class="text-blue-400 hover:text-blue-500 text-sm flex items-center gap-1 cursor-pointer">
                                <x-heroicon-o-information-circle class="w-5 h-5" />
                                Overage Calculation
                            </span>

                            <!-- Tooltip -->
                            <div class="tooltip-panel tooltip-panel--center">
                                *Overage Rate / Hr. is automatically calculated based upon your Overage
                                Percentage setting in Settings.
                                <br><br>
                                <strong>Overage Rate</strong> Rate is a percentage used to compute the <strong>hourly
                                    overage
                                    fee</strong> for this product.
                                <br><br>
                                <strong>
                                    Formula:</strong> Hourly Overage = <strong>Daily Rental Rate × Overage Rate (%).
                                </strong>
                                <br>
                                If a renter exceeds their allocated hours (e.g., 8/day, 14/weekend, 40/week, 160/month),
                                this
                                fee applies <strong>for each hour over.</strong>
                                <br><br>
                                <strong>Example:</strong> Daily Rate <strong>$300</strong> × Overage Rate <strong>10% =
                                    $30/hour</strong> over the limit.
                                <br><br>
                                <strong>Note:</strong> Partial hours are billed as a full hour.
                            </div>
                        </span>
                    </label>
                </div>
            </div> --}}

        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Delivery Fee Row -->
        <div class="grid grid-cols-1 lg:grid-cols-4 xl:grid-cols-4 gap-4 mt-4 items-start"><!-- ⬅️ was items-end -->
            <!-- Delivery Fees Group -->
            <div class="lg:col-span-2 xl:col-span-2">
                <label class="block w-full text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Truck Delivery / Pickup Fee
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Standard Delivery Fee -->
                    <div class="flex flex-col items-center">
                        <div class="flex items-center gap-1 w-full">
                            <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                            {!! html()->text('standard_delivery_fee')->attributes([
                                    'placeholder' => '0',
                                    'data-numeric-input' => 'true',
                                    'data-parsley-errors-container' => '#delivery-fee-error',
                                    'class' =>
                                        'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                ]) !!}
                        </div>
                        <span
                            class="mt-1 inline-block text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 border rounded text-gray-600 dark:text-white text-center">
                            {{ $productSettings['standard_delivery_range'] ?? 0 }}
                            {{ $productSettings['distance_unit'] }}
                        </span>
                    </div>

                    @if ($productSettings['include_extended_range'] ?? false)
                        <div class="flex flex-col items-center">
                            <div class="flex items-center gap-1 w-full">
                                <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                                {!! html()->text('extended_delivery_fee')->attributes([
                                        'placeholder' => '0',
                                        'data-numeric-input' => 'true',
                                        'data-parsley-errors-container' => '#delivery-fee-error',
                                        'class' =>
                                            'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                    ]) !!}
                            </div>
                            <span
                                class="mt-1 inline-block text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 border rounded text-gray-600 dark:text-white text-center">
                                {{ $productSettings['extended_delivery_range'] ?? 0 }}
                                {{ $productSettings['distance_unit'] }}
                            </span>
                        </div>
                    @endif
                </div>

                <div id="delivery-fee-error" class="text-xs text-red-500 mt-1"></div>
                @error('standard_delivery_fee')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror
                @error('extended_delivery_fee')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Delivery Type -->
            <div class="lg:col-span-2 xl:col-span-2">
                <label class="block w-full text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Delivery Type
                </label>

                <!-- slight top margin to align with inputs nicely -->
                <div class="flex items-center gap-6 mt-1">
                    <label class="flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                        {!! html()->checkbox('in_store_pickup', old('in_store_pickup', $objProduct->in_store_pickup ?? 'Yes') === 'Yes', 'Yes')->class('mr-2')->attribute('data-parsley-errors-container', '#in-store-pickup-errors') !!}
                        In Store Pick Up
                    </label>

                    <label class="flex items-center text-xs font-medium text-gray-700 dark:text-gray-300">
                        {!! html()->checkbox(
                                'delivery_and_pickup',
                                old('delivery_and_pickup', $objProduct->delivery_and_pickup ?? 'Yes') === 'Yes',
                                'Yes',
                            )->class('mr-2')->attribute('data-parsley-errors-container', '#delivery-and-pickup-errors') !!}
                        Truck Delivery / Pickup
                    </label>
                </div>

                <div id="in-store-pickup-errors"></div>
                @error('in_store_pickup')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror

                <div id="delivery-and-pickup-errors"></div>
                @error('delivery_and_pickup')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror

            </div>
        </div>

        {{-- Sizes --}}
        <div class="mt-3">

            <div class="flex items-center flex-wrap gap-6">
                <label for="size_small"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'truck_fee_size_setting',
                            old('truck_fee_size_setting', $objProduct->truck_fee_size_setting ?? null) == 'Small',
                            'Small',
                        )->id('size_small')->class('mr-2')->attribute('data-parsley-errors-container', '#sizes-errors') !!}
                    Small
                </label>

                <label for="size_medium"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'truck_fee_size_setting',
                            old('truck_fee_size_setting', $objProduct->truck_fee_size_setting ?? null) == 'Medium',
                            'Medium',
                        )->id('size_medium')->class('mr-2')->attribute('data-parsley-errors-container', '#sizes-errors') !!}
                    Medium
                </label>

                <label for="size_large"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'truck_fee_size_setting',
                            old('truck_fee_size_setting', $objProduct->truck_fee_size_setting ?? null) == 'Large',
                            'Large',
                        )->id('size_large')->class('mr-2')->attribute('data-parsley-errors-container', '#sizes-errors') !!}
                    Large
                </label>

                <label for="size_xlarge"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'truck_fee_size_setting',
                            old('truck_fee_size_setting', $objProduct->truck_fee_size_setting ?? null) == 'X-Large',
                            'X-Large',
                        )->id('size_xlarge')->class('mr-2')->attribute('data-parsley-errors-container', '#sizes-errors') !!}
                    X-Large
                </label>

                <label for="size_2xlarge"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'truck_fee_size_setting',
                            old('truck_fee_size_setting', $objProduct->truck_fee_size_setting ?? null) == '2X-Large',
                            '2X-Large',
                        )->id('size_2xlarge')->class('mr-2')->attribute('data-parsley-errors-container', '#sizes-errors') !!}
                    2X-Large
                </label>

                <label for="size_commercial"
                    class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'truck_fee_size_setting',
                            old('truck_fee_size_setting', $objProduct->truck_fee_size_setting ?? null) == 'Commercial',
                            'Commercial',
                        )->id('size_commercial')->class('mr-2')->attribute('data-parsley-errors-container', '#sizes-errors') !!}
                    Commercial
                </label>
            </div>

            <div id="sizes-errors"></div>
        </div>


        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>


        <!-- High Demand Alert -->
        <div class="grid grid-cols-1  gap-4">
            <div class="flex items-center gap-2 mt-1 relative">
                {!! html()->checkbox('has_high_demand_alert') !!}
                <label for="has_high_demand_alert" class="text-gray-700 dark:text-gray-300">
                    High Demand Alert
                </label>
                <!-- Tooltip trigger -->
                <div class="relative group">
                    <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center cursor-pointer">
                        <x-heroicon-o-information-circle class="w-5 h-5" />
                    </span>

                    <!-- Tooltip -->
                    <div class="tooltip-panel max-w-sm">
                        <p>
                            Display a checkout alert to inform customers that this product is in high demand or has limited availability.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-8">
                <label for="is_tax_free_item" class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        id="is_tax_free_item"
                        name="is_tax_free_item"
                        value="1"
                        @checked(old('is_tax_free_item', $objProduct->is_tax_free_item ?? false))>
                    <span class="text-gray-700 dark:text-gray-300">Tax Free Item</span>
                    <span class="relative group" aria-label="Tax Free Item info">
                        <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center cursor-pointer">
                            <x-heroicon-o-information-circle class="w-5 h-5" />
                        </span>
                        <span class="tooltip-panel max-w-sm">
                            Sales tax is applied to all products by default. Enable this option to override standard tax behavior for this product.
                        </span>
                    </span>
                </label>

                <label for="apply_special_tax" class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        id="apply_special_tax"
                        name="apply_special_tax"
                        value="1"
                        @checked(old('apply_special_tax', $objProduct->apply_special_tax ?? false))>
                    <span class="text-gray-700 dark:text-gray-300">Apply Special Tax</span>
                    <span class="relative group" aria-label="Apply Special Tax info">
                        <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center cursor-pointer">
                            <x-heroicon-o-information-circle class="w-5 h-5" />
                        </span>
                        <span class="tooltip-panel max-w-sm">
                            Apply Special Tax to the base product only. Optional add-ons such as Prepaid Fuel,
                            Prepaid Cleaning, Damage Waiver Protection, and Track Coverage are not taxed.
                        </span>
                    </span>
                </label>

                <label for="apply_added_fees" class="inline-flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        id="apply_added_fees"
                        name="apply_added_fees"
                        value="1"
                        @checked(old('apply_added_fees', $objProduct->apply_added_fees ?? false))>
                    <span class="text-gray-700 dark:text-gray-300">Apply Added Fees</span>
                    <span class="relative group" aria-label="Apply Added Fees info">
                        <span class="text-blue-400 hover:text-blue-500 text-sm flex items-center cursor-pointer">
                            <x-heroicon-o-information-circle class="w-5 h-5" />
                        </span>
                        <span class="tooltip-panel tooltip-panel--right max-w-sm">
                            Apply one-time fees to the order for special situations (e.g., environmental or disposal
                            fees). These fees increase based on the number of products in the order.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="h-3 md:h-4"></div>
        @include('admin.product_management.products.partials._terms_checklist')

        <div class="mt-auto flex justify-end pt-25">
            <a href="{{ route('admin.configurations.index') }}" target="_blank"
                class="text-sm text-blue-500 hover:underline font-medium">
                Update Settings
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        const damageWaiverPercentage = "{{ $productSettings['damage_waiver_percentage'] }}"
        const overageRatePercentage = "{{ $productSettings['overage_rate_percentage'] }}";
        const weekendRateMultiplier = "{{ $priceRateMultiplierSettings['weekend_multiplier'] ?? '' }}";
        const weeklyRateMultiplier = "{{ $priceRateMultiplierSettings['weekly_multiplier'] ?? '' }}";
        const monthlyRateMultiplier = "{{ $priceRateMultiplierSettings['monthly_multiplier'] ?? '' }}";

        // Auto-fill delivery fees based on selected size
        const sizeFeeMap = {
            Small: {
                standard: "{{ $productSettings['small_standard_delivery_fee_formatted'] ?? '' }}",
                extended: "{{ $productSettings['small_extended_delivery_fee_formatted'] ?? '' }}"
            },
            Medium: {
                standard: "{{ $productSettings['medium_standard_delivery_fee_formatted'] ?? '' }}",
                extended: "{{ $productSettings['medium_extended_delivery_fee_formatted'] ?? '' }}"
            },
            Large: {
                standard: "{{ $productSettings['large_standard_delivery_fee_formatted'] ?? '' }}",
                extended: "{{ $productSettings['large_extended_delivery_fee_formatted'] ?? '' }}"
            },
            "X-Large": {
                standard: "{{ $productSettings['x_large_standard_delivery_fee_formatted'] ?? '' }}",
                extended: "{{ $productSettings['x_large_extended_delivery_fee_formatted'] ?? '' }}"
            },
            "2X-Large": {
                standard: "{{ $productSettings['2x_large_standard_delivery_fee_formatted'] ?? '' }}",
                extended: "{{ $productSettings['2x_large_extended_delivery_fee_formatted'] ?? '' }}"
            },
            Commercial: {
                standard: "{{ $productSettings['commercial_standard_delivery_fee_formatted'] ?? '' }}",
                extended: "{{ $productSettings['commercial_extended_delivery_fee_formatted'] ?? '' }}"
            }
        };


        // Auto-fill track insurance based on selected size
        const sizeTrackInsuranceFeeMap = {
            Small: {
                daily: "{{ $productSettings['small_daily_track_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['small_weekend_track_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['small_weekly_track_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['small_monthly_track_insurance_fee'] ?? '' }}"
            },
            Medium: {
                daily: "{{ $productSettings['medium_daily_track_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['medium_weekend_track_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['medium_weekly_track_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['medium_monthly_track_insurance_fee'] ?? '' }}"
            },
            Large: {
                daily: "{{ $productSettings['large_daily_track_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['large_weekend_track_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['large_weekly_track_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['large_monthly_track_insurance_fee'] ?? '' }}"
            },
            "X-Large": {
                daily: "{{ $productSettings['x_large_daily_track_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['x_large_weekend_track_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['x_large_weekly_track_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['x_large_monthly_track_insurance_fee'] ?? '' }}"
            },
            "2X-Large": {
                daily: "{{ $productSettings['2x_large_daily_track_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['2x_large_weekend_track_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['2x_large_weekly_track_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['2x_large_monthly_track_insurance_fee'] ?? '' }}"
            },
            Commercial: {
                daily: "{{ $productSettings['commercial_daily_track_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['commercial_weekend_track_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['commercial_weekly_track_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['commercial_monthly_track_insurance_fee'] ?? '' }}"
            }
        };

        // Auto-fill tire insurance based on selected size
        const sizeTireInsuranceFeeMap = {
            Small: {
                daily: "{{ $productSettings['small_daily_tire_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['small_weekend_tire_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['small_weekly_tire_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['small_monthly_tire_insurance_fee'] ?? '' }}"
            },
            Medium: {
                daily: "{{ $productSettings['medium_daily_tire_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['medium_weekend_tire_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['medium_weekly_tire_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['medium_monthly_tire_insurance_fee'] ?? '' }}"
            },
            Large: {
                daily: "{{ $productSettings['large_daily_tire_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['large_weekend_tire_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['large_weekly_tire_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['large_monthly_tire_insurance_fee'] ?? '' }}"
            },
            "X-Large": {
                daily: "{{ $productSettings['x_large_daily_tire_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['x_large_weekend_tire_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['x_large_weekly_tire_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['x_large_monthly_tire_insurance_fee'] ?? '' }}"
            },
            "2X-Large": {
                daily: "{{ $productSettings['2x_large_daily_tire_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['2x_large_weekend_tire_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['2x_large_weekly_tire_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['2x_large_monthly_tire_insurance_fee'] ?? '' }}"
            },
            Commercial: {
                daily: "{{ $productSettings['commercial_daily_tire_insurance_fee'] ?? '' }}",
                weekend: "{{ $productSettings['commercial_weekend_tire_insurance_fee'] ?? '' }}",
                weekly: "{{ $productSettings['commercial_weekly_tire_insurance_fee'] ?? '' }}",
                monthly: "{{ $productSettings['commercial_monthly_tire_insurance_fee'] ?? '' }}"
            }
        };

        // console.log(sizeFeeMap,sizeTrackInsuranceFeeMap);
        document.addEventListener("DOMContentLoaded", function() {
            // const hourTracking = document.querySelector('input[name="hour_tracking"]');
            // const hourRateInput = document.querySelector('input[name="hour_rate"]');

            // function toggleHourRateReadonly() {
            //     if (hourTracking.checked) {
            //         hourRateInput.removeAttribute("readonly");
            //     } else {
            //         hourRateInput.value = "";
            //         hourRateInput.setAttribute("readonly", true);
            //     }
            // }

            // if (hourTracking && hourRateInput) {
            //     hourTracking.addEventListener("change", toggleHourRateReadonly);
            //     toggleHourRateReadonly();
            // }

            // Damage Waiver Auto-Calculation
            const dailyPriceInput = document.querySelector('input[name="rental_daily"]');
            const weekendPriceInput = document.querySelector('input[name="rental_weekend"]');
            const weeklyPriceInput = document.querySelector('input[name="rental_weekly"]');
            const monthlyPriceInput = document.querySelector('input[name="rental_monthly"]');

            const damageWaiverDailyInput = document.querySelector('input[name="rental_damage_waiver_daily"]');
            const damageWaiverWeekendInput = document.querySelector('input[name="rental_damage_waiver_weekend"]');
            const damageWaiverWeeklyInput = document.querySelector('input[name="rental_damage_waiver_weekly"]');
            const damageWaiverMonthlyInput = document.querySelector('input[name="rental_damage_waiver_monthly"]');

            function calculateOverageRate(price) {
                const percentage = parseFloat(overageRatePercentage);
                if (isNaN(price) || isNaN(percentage)) return 0;
                return Math.round((price * percentage) / 100).toFixed(2);
            }

            function updateOverageRate(inputElement, overageElement) {
                const price = parseFloat(inputElement.value);
                if (!isNaN(price) && price > 0) {
                    overageElement.value = calculateOverageRate(price);
                } else {
                    overageElement.value = "";
                }
            }

            function calculateDamageWaiver(price) {
                const percentage = parseFloat(damageWaiverPercentage);
                if (isNaN(price) || isNaN(percentage)) return 0;
                return Math.round((price * percentage) / 100).toFixed(2);
            }

            function updateDamageWaiver(inputElement, waiverElement) {
                const price = parseFloat(inputElement.value);
                if (!isNaN(price) && price > 0) {
                    waiverElement.value = calculateDamageWaiver(price);
                } else {
                    waiverElement.value = "";
                }
            }

            function calculatePriceByMultiplier(dailyPrice, multiplier) {
                const parsedDailyPrice = parseFloat(dailyPrice);
                const parsedMultiplier = parseFloat(multiplier);

                if (isNaN(parsedDailyPrice) || parsedDailyPrice <= 0 || isNaN(parsedMultiplier) || parsedMultiplier <=
                    0) {
                    return "";
                }

                return (parsedDailyPrice * parsedMultiplier).toFixed(2);
            }

            function updateCalculatedRentalPricesFromDaily() {
                const dailyPrice = dailyPriceInput.value;

                weekendPriceInput.value = calculatePriceByMultiplier(dailyPrice, weekendRateMultiplier);
                weeklyPriceInput.value = calculatePriceByMultiplier(dailyPrice, weeklyRateMultiplier);
                monthlyPriceInput.value = calculatePriceByMultiplier(dailyPrice, monthlyRateMultiplier);

                updateDamageWaiver(weekendPriceInput, damageWaiverWeekendInput);
                updateDamageWaiver(weeklyPriceInput, damageWaiverWeeklyInput);
                updateDamageWaiver(monthlyPriceInput, damageWaiverMonthlyInput);
            }

            dailyPriceInput.addEventListener("input", () => {
                // if (hourTracking.checked) {
                //     updateOverageRate(dailyPriceInput, hourRateInput);
                // }
                updateCalculatedRentalPricesFromDaily();
                updateDamageWaiver(dailyPriceInput, damageWaiverDailyInput);
            });

            weekendPriceInput.addEventListener("input", () => {
                updateDamageWaiver(weekendPriceInput, damageWaiverWeekendInput);
            });

            weeklyPriceInput.addEventListener("input", () => {
                updateDamageWaiver(weeklyPriceInput, damageWaiverWeeklyInput);
            });

            monthlyPriceInput.addEventListener("input", () => {
                updateDamageWaiver(monthlyPriceInput, damageWaiverMonthlyInput);
            });

            const standardDeliveryFeeInput = document.querySelector('input[name="standard_delivery_fee"]');
            const extendedDeliveryFeeInput = document.querySelector('input[name="extended_delivery_fee"]');
            const deliveryAndPickupInput = document.querySelector('input[name="delivery_and_pickup"]');

            standardDeliveryFeeInput?.addEventListener('input', function() {
                if (this.value && deliveryAndPickupInput.checked) {
                    // If user manually changes, uncheck sizes
                    //sizeCheckboxes.forEach(cb => cb.checked = false);
                }
            });

            extendedDeliveryFeeInput?.addEventListener('input', function() {
                if (this.value && deliveryAndPickupInput.checked) {
                    // If user manually changes, uncheck sizes
                    //sizeCheckboxes.forEach(cb => cb.checked = false);
                }
            });

            deliveryAndPickupInput?.addEventListener('change', function() {
                if (!this.checked) {
                    // If delivery is unchecked, clear fees and sizes
                    if (standardDeliveryFeeInput) standardDeliveryFeeInput.value = '';
                    if (extendedDeliveryFeeInput) extendedDeliveryFeeInput.value = '';
                    sizeCheckboxes.forEach(cb => cb.checked = false);
                } else {
                    // If checked and a size is selected, auto-fill fees
                    const selectedSize = sizeCheckboxes.find(cb => cb.checked);
                    if (selectedSize) {
                        const fees = sizeFeeMap[selectedSize.value];
                        if (fees) {
                            if (standardDeliveryFeeInput) standardDeliveryFeeInput.value = fees.standard;
                            if (extendedDeliveryFeeInput) extendedDeliveryFeeInput.value = fees.extended;
                        }
                    }
                }
            });

            // Make size checkboxes behave like single-select (no radios)
            const sizeCheckboxes = Array.from(document.querySelectorAll('input[name="truck_fee_size_setting"]'));

            sizeCheckboxes.forEach(cb => {
                cb.addEventListener('change', function(e) {
                    // Prevent checking if delivery is not enabled
                    if (!deliveryAndPickupInput.checked) {
                        cb.checked = false;
                        return;
                    }

                    // Auto-fill delivery fees if delivery is enabled
                    if (cb.checked && deliveryAndPickupInput.checked) {
                        const fees = sizeFeeMap[cb.value];
                        if (fees) {
                            if (standardDeliveryFeeInput) standardDeliveryFeeInput.value = fees
                                .standard;
                            if (extendedDeliveryFeeInput) extendedDeliveryFeeInput.value = fees
                                .extended;
                        }
                    } else {
                        // If unchecked, clear fees
                        if (standardDeliveryFeeInput) standardDeliveryFeeInput.value = '';
                        if (extendedDeliveryFeeInput) extendedDeliveryFeeInput.value = '';
                    }
                    enforceSingleSizeSelection(e.target);
                });
            });

            function enforceSingleSizeSelection(changed) {
                if (!sizeCheckboxes.length) return;
                if (changed && changed.checked) {
                    sizeCheckboxes.forEach(cb => {
                        if (cb !== changed) cb.checked = false;
                    });
                } else {
                    const checked = sizeCheckboxes.filter(cb => cb.checked);
                    if (checked.length > 1) {
                        checked.slice(1).forEach(cb => (cb.checked = false));
                    }
                }
            }

            // Normalize initial state in case old()/model sets multiple
            enforceSingleSizeSelection();


            const rentalTrackInsuranceInputs = [
                document.querySelector('input[name="rental_track_insurance_daily"]'),
                document.querySelector('input[name="rental_track_insurance_weekend"]'),
                document.querySelector('input[name="rental_track_insurance_weekly"]'),
                document.querySelector('input[name="rental_track_insurance_monthly"]')
            ];
            // Make size checkboxes behave like single-select (no radios)
            const sizeTrackInsuranceCheckboxes = Array.from(document.querySelectorAll(
                'input[name="track_insurance_size_setting"]'));

            rentalTrackInsuranceInputs.forEach(input => {
                input.addEventListener('input', function() {
                    // if (this.value) {
                    //     // If user manually changes, uncheck sizes
                    //     sizeTrackInsuranceCheckboxes.forEach(cb => cb.checked = false);
                    // }
                });
            });

            sizeTrackInsuranceCheckboxes.forEach(cb => {
                cb.addEventListener('change', function(e) {
                    // Auto-fill track insurance fees if track insurance is enabled
                    if (cb.checked) {
                        const fees = sizeTrackInsuranceFeeMap[cb.value];
                        if (fees) {
                            if (rentalTrackInsuranceInputs[0]) rentalTrackInsuranceInputs[0].value =
                                fees
                                .daily;
                            if (rentalTrackInsuranceInputs[1]) rentalTrackInsuranceInputs[1].value =
                                fees
                                .weekend;
                            if (rentalTrackInsuranceInputs[2]) rentalTrackInsuranceInputs[2].value =
                                fees
                                .weekly;
                            if (rentalTrackInsuranceInputs[3]) rentalTrackInsuranceInputs[3].value =
                                fees
                                .monthly;
                        }
                    } else {
                        // If unchecked, clear track insurance fees
                        rentalTrackInsuranceInputs.forEach(input => input.value = '');
                    }
                    enforceTrackInsuranceSingleSizeSelection(e.target);
                });
            });

            function enforceTrackInsuranceSingleSizeSelection(changed) {
                if (!sizeTrackInsuranceCheckboxes.length) return;
                if (changed && changed.checked) {
                    sizeTrackInsuranceCheckboxes.forEach(cb => {
                        if (cb !== changed) cb.checked = false;
                    });
                } else {
                    const checked = sizeTrackInsuranceCheckboxes.filter(cb => cb.checked);
                    if (checked.length > 1) {
                        checked.slice(1).forEach(cb => (cb.checked = false));
                    }
                }
            }

            enforceTrackInsuranceSingleSizeSelection();

            const rentalTireInsuranceInputs = [
                document.querySelector('input[name="rental_tire_insurance_daily"]'),
                document.querySelector('input[name="rental_tire_insurance_weekend"]'),
                document.querySelector('input[name="rental_tire_insurance_weekly"]'),
                document.querySelector('input[name="rental_tire_insurance_monthly"]')
            ];
            // Make tire insurance size checkboxes behave like single-select (no radios)
            const sizeTireInsuranceCheckboxes = Array.from(document.querySelectorAll(
                'input[name="tire_insurance_size_setting"]'));

            sizeTireInsuranceCheckboxes.forEach(cb => {
                cb.addEventListener('change', function(e) {
                    // Auto-fill tire insurance fees if a size is selected
                    if (cb.checked) {
                        const fees = sizeTireInsuranceFeeMap[cb.value];
                        if (fees) {
                            if (rentalTireInsuranceInputs[0]) rentalTireInsuranceInputs[0].value = fees.daily;
                            if (rentalTireInsuranceInputs[1]) rentalTireInsuranceInputs[1].value = fees.weekend;
                            if (rentalTireInsuranceInputs[2]) rentalTireInsuranceInputs[2].value = fees.weekly;
                            if (rentalTireInsuranceInputs[3]) rentalTireInsuranceInputs[3].value = fees.monthly;
                        }
                    } else {
                        // If unchecked, clear tire insurance fees
                        rentalTireInsuranceInputs.forEach(input => { if (input) input.value = ''; });
                    }
                    enforceTireInsuranceSingleSizeSelection(e.target);
                });
            });

            function enforceTireInsuranceSingleSizeSelection(changed) {
                if (!sizeTireInsuranceCheckboxes.length) return;
                if (changed && changed.checked) {
                    sizeTireInsuranceCheckboxes.forEach(cb => {
                        if (cb !== changed) cb.checked = false;
                    });
                } else {
                    const checked = sizeTireInsuranceCheckboxes.filter(cb => cb.checked);
                    if (checked.length > 1) {
                        checked.slice(1).forEach(cb => (cb.checked = false));
                    }
                }
            }

            enforceTireInsuranceSingleSizeSelection();

            const prepaidCleaningRates = document.getElementById('prepaid_cleaning_rate_setting');
            const prepaidFuelRates = document.getElementById('prepaid_fuel_rate_setting');

            const prepaidCleaningInput = document.querySelector('input[name="rental_prepaid_cleaning"]');
            const prepaidFuelInput = document.querySelector('input[name="rental_prepaid_fuel"]');

            prepaidCleaningRates?.addEventListener('change', function() {
                const dataRate = this.options[this.selectedIndex].getAttribute('data-rate');
                if (dataRate) {
                    prepaidCleaningInput.value = dataRate;
                } else {
                    prepaidCleaningInput.value = '';
                }
            });

            prepaidFuelRates?.addEventListener('change', function() {
                const dataRate = this.options[this.selectedIndex].getAttribute('data-rate');
                if (dataRate) {
                    prepaidFuelInput.value = dataRate;
                } else {
                    prepaidFuelInput.value = '';
                }
            });

            prepaidFuelInput?.addEventListener('input', function() {
                if (this.value) {
                    prepaidFuelRates.value = '';
                }
            });

            prepaidCleaningInput?.addEventListener('input', function() {
                if (this.value) {
                    prepaidCleaningRates.value = '';
                }
            });

        });
    </script>
@endpush

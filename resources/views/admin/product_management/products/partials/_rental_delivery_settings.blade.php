<div class="grid grid-cols-1 lg:grid-cols-2 gap-1">
    <!-- Rental Configuration -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Rental Configuration</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Daily -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Daily</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_daily')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">8 Hrs Allocated</p>
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
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">14 Hrs Allocated</p>
                <div id="rental-weekend-errors"></div>
            </div>

            <!-- Weekly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Weekly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_weekly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">40 Hrs Allocated</p>
                <div id="rental-weekly-errors"></div>
            </div>

            <!-- Monthly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 required">Monthly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_monthly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-monthly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">160 Hrs Allocated</p>
                <div id="rental-monthly-errors"></div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_daily')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-damage-waiver-daily-errors"></div>
            </div>

            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_weekend')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-damage-waiver-weekend-errors"></div>
            </div>

            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_weekly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-damage-waiver-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-damage-waiver-weekly-errors"></div>
            </div>

            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_damage_waiver_monthly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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

        <!-- Prepaid Options -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 ">
            <!-- Prepaid Cleaning -->
            <div>
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid
                    Cleaning</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_prepaid_cleaning')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid
                    Fuel</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_prepaid_fuel')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-prepaid-fuel-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-prepaid-fuel-errors"></div>
            </div>

            <!-- Gallons + Fuel Type -->
            <div>
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gallons
                    - Fuel</label>
                {!! html()->text('rental_fuel_gallons')->attributes([
                        'placeholder' => '0',
                        'data-digit-input' => 'true',
                        'data-parsley-maxlength' => 8,
                        'maxlength' => 8,
                        'data-parsley-errors-container' => '#rental-fuel-gallons-errors',
                        'class' =>
                            'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
                <div id="rental-fuel-gallons-errors"></div>
                @php
                    $selectedFuelType = old('rental_fuel_type', $objProduct->rental_fuel_type ?? 'Diesel');
                @endphp

                <div class="flex items-center gap-4 mt-2">
                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                        {!! html()->radio('rental_fuel_type', $selectedFuelType === 'Diesel', 'Diesel')->id('fuel_diesel')->class('mr-1') !!} Diesel
                    </label>

                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                        {!! html()->radio('rental_fuel_type', $selectedFuelType === 'Gas', 'Gas')->id('fuel_gas')->class('mr-1') !!} Gas
                    </label>
                </div>

            </div>

            <div>
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gallons
                    - Def</label>
                {!! html()->text('rental_def_gallons')->attributes([
                        'placeholder' => '0',
                        'data-digit-input' => 'true',
                        'data-parsley-maxlength' => 8,
                        'maxlength' => 8,
                        'data-parsley-errors-container' => '#rental-def-gallons-errors',
                        'class' =>
                            'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
                <div id="rental-def-gallons-errors"></div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>
    </div>

    <!-- Sale Prices -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Sale Prices</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Daily -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Daily</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_daily')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weekend Spcl.</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_weekend')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weekly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_weekly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('sale_price_monthly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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

        <!-- Delivery Fee Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 mt-4 items-end">
            <!-- Standard Delivery Fee -->
            <div class="flex flex-col items-center">
                <label
                    class="block w-full text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-left required">Delivery
                    Fee</label>
                <div class="flex items-center gap-1 w-full">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('standard_delivery_fee')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-errors-container' => '#standard-delivery-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="standard-delivery-errors"></div>
                @error('standard_delivery_fee')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror
                <span
                    class="mt-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 border rounded text-gray-600 dark:text-white text-center">
                    {{ $productSettings['standard_delivery_range'] ?? 0 }} {{ $productSettings['distance_unit'] }}
                </span>
            </div>

            @if ($productSettings['include_extended_range'] ?? false)
                <!-- Extended Delivery Fee -->
                <div class="flex flex-col items-center">
                    <label class="block w-full text-sm font-medium text-transparent mb-1 select-none">-</label>
                    <div class="flex items-center gap-1 w-full">
                        <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                        {!! html()->text('extended_delivery_fee')->attributes([
                                'placeholder' => '0',
                                'data-digit-input' => 'true',
                                'data-parsley-errors-container' => '#extended-delivery-errors',
                                'class' =>
                                    'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                            ]) !!}
                    </div>
                    <div id="extended-delivery-errors"></div>
                    @error('extended_delivery_fee')
                        <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                    @enderror
                    <span
                        class="mt-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 border rounded text-gray-600 dark:text-white text-center">
                        {{ $productSettings['extended_delivery_range'] ?? 0 }} {{ $productSettings['distance_unit'] }}
                    </span>
                </div>
            @endif

            <!-- Shared error message row -->
            <div class="lg:col-span-2 xl:col-span-4">
                <div id="delivery-fee-error" class="text-xs text-red-500 mt-1"></div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Pickup Options -->
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex flex-col">
                <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox('in_store_pickup', old('in_store_pickup', $objProduct->in_store_pickup ?? 'Yes') === 'Yes', 'Yes')->class('mr-2')->attribute('data-parsley-errors-container', '#in-store-pickup-errors') !!}
                    In Store Pick Up
                </label>
                <div id="in-store-pickup-errors"></div>
                @error('in_store_pickup')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex flex-col">
                <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {!! html()->checkbox(
                            'delivery_and_pickup',
                            old('delivery_and_pickup', $objProduct->delivery_and_pickup ?? 'Yes') === 'Yes',
                            'Yes',
                        )->class('mr-2')->attribute('data-parsley-errors-container', '#delivery-and-pickup-errors') !!}
                    Delivery and Pickup
                </label>
                <div id="delivery-and-pickup-errors"></div>
                @error('delivery_and_pickup')
                    <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                @enderror
            </div>
        </div>



        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Wrap the section in Alpine.js -->
        <div x-data="{ hourTracking: {{ old('hour_tracking', $objProduct->hour_tracking ?? false) === 'Yes' ? 'true' : 'false' }} }" class="flex flex-wrap items-center gap-4 mt-6">

            <!-- Checkbox + Label -->
            <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 m-0">
                <input type="checkbox" name="hour_tracking" value="Yes" x-model="hourTracking"
                    :checked="hourTracking"
                    class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700" />
                Hour Tracking
            </label>
            @error('hour_tracking')
                <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
            @enderror

            <!-- Overage Rate Input -->
            <div class="flex items-center text-sm text-gray-700 dark:text-gray-300 gap-1">
                <span class="text-gray-500">$</span>
                <input type="text" name="hour_rate" placeholder="0" x-bind:disabled="!hourTracking"
                    x-bind:value="hourTracking ? '{{ old('hour_rate', $objProduct->hour_rate ?? '') }}' : ''"
                    data-digit-input="true" data-parsley-maxlength="8" maxlength="8"
                    data-parsley-errors-container="#hour-rate-errors"
                    class="w-24 rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white" />
                <span>Overage Rate / Hr.</span>
            </div>
            <div id="hour-rate-errors"></div>
            @error('hour_rate')
                <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
            @enderror

            <!-- Update Link -->
            <a href="{{ route('admin.configurations.index') }}" target="_blank"
                class="text-sm text-blue-500 hover:underline font-medium ml-auto">
                Update Settings
            </a>
        </div>


    </div>
</div>

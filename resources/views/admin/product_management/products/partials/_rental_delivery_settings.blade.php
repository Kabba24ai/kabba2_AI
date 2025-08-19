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

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Truck
                    Insurance</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_truck_insurance_daily')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-truck-insurance-daily-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-truck-insurance-daily-errors"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Truck
                    Insurance</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_truck_insurance_weekend')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-truck-insurance-weekend-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-truck-insurance-weekend-errors"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Truck
                    Insurance</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_truck_insurance_weekly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'data-parsley-errors-container' => '#rental-truck-insurance-weekly-errors',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <div id="rental-truck-insurance-weekly-errors"></div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Truck
                    Insurance</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('rental_truck_insurance_monthly')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Sale Prices -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 ">
            <!-- Daily -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sale Price</label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sale Price</label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sale Price</label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sale Price</label>
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
    </div>

    <!-- Additional Prices -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Additional Rental Prices</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
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

            <div>
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Overage Rate / Hr.
                </label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">{{ config('app.currency.code') }}</span>
                    {!! html()->text('hour_rate')->attributes([
                            'placeholder' => '0',
                            'data-digit-input' => 'true',
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

            </div>
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
                                    'data-digit-input' => 'true',
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
                                        'data-digit-input' => 'true',
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


        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

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
        document.addEventListener("DOMContentLoaded", function() {
            const hourTracking = document.querySelector('input[name="hour_tracking"]');
            const hourRate = document.querySelector('input[name="hour_rate"]');

            function toggleHourRateReadonly() {
                if (hourTracking.checked) {
                    hourRate.removeAttribute("readonly");
                } else {
                    hourRate.value = "";
                    hourRate.setAttribute("readonly", true);
                }
            }

            if (hourTracking && hourRate) {
                hourTracking.addEventListener("change", toggleHourRateReadonly);
                toggleHourRateReadonly();
            }
        });
    </script>
@endpush

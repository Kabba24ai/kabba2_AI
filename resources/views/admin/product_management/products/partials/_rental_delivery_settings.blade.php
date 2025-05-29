<div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-6 space-y-6">
    <!-- Rental Rates -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach (['daily' => ['label' => 'Daily', 'hours' => '8 Hrs Allocated'], 'weekend' => ['label' => 'Weekend Spcl.', 'hours' => '14 Hrs Allocated'], 'weekly' => ['label' => 'Weekly', 'hours' => '40 Hrs Allocated'], 'monthly' => ['label' => 'Monthly', 'hours' => '160 Hrs Allocated']] as $key => $term)
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $term['label'] }}</label>
                {!! html()->text("rental_prices[$key]")->attributes([
                        'placeholder' => '$ 0',
                        'class' =>
                            'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
                <p class="text-xs mt-1 text-gray-500">{{ $term['hours'] }}</p>

                <label class="block mt-2 text-xs text-gray-500">Damage Waiver</label>
                {!! html()->text("damage_waivers[$key]")->attributes([
                        'placeholder' => '$ 0',
                        'class' =>
                            'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
            </div>
        @endforeach
    </div>

    <!-- Add-ons: Cleaning, Fuel -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid Cleaning</label>
            {!! html()->text('prepaid_cleaning')->attributes([
                    'placeholder' => '$ 0',
                    'class' =>
                        'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                ]) !!}
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid Fuel</label>
            {!! html()->text('prepaid_fuel')->attributes([
                    'placeholder' => '$ 0',
                    'class' =>
                        'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                ]) !!}
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gallons</label>
            <div class="flex items-center gap-3">
                {!! html()->text('fuel_gallons')->attributes([
                        'placeholder' => '0',
                        'class' =>
                            'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-6" x-data="{ selectedFuel: '{{ old('fuel_type', 'diesel') }}', hourTracking: {{ old('hour_tracking', true) ? 'true' : 'false' }} }">
        <!-- Fuel Type (Custom Radio Buttons) -->
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-800 dark:text-white/90">Fuel Type:</label>

            <!-- Diesel -->
            <label
                :class="selectedFuel === 'diesel' ? 'text-gray-700 dark:text-gray-400' : 'text-gray-500 dark:text-gray-400'"
                class="relative flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                {!! html()->radio('fuel_type', false, 'diesel')->id('fuel_diesel')->class('sr-only')->attribute('@change', "selectedFuel = 'diesel'") !!}
                <span
                    :class="selectedFuel === 'diesel' ? 'border-blue-500 bg-blue-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                    class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                    <span :class="selectedFuel === 'diesel' ? 'block' : 'hidden'"
                        class="w-2 h-2 bg-white rounded-full"></span>
                </span>
                Diesel
            </label>

            <!-- Gas -->
            <label
                :class="selectedFuel === 'gas' ? 'text-gray-700 dark:text-gray-400' : 'text-gray-500 dark:text-gray-400'"
                class="relative flex items-center gap-2 text-sm font-medium cursor-pointer select-none">
                {!! html()->radio('fuel_type', false, 'gas')->id('fuel_gas')->class('sr-only')->attribute('@change', "selectedFuel = 'gas'") !!}
                <span
                    :class="selectedFuel === 'gas' ? 'border-blue-500 bg-blue-500' :
                        'bg-transparent border-gray-300 dark:border-gray-700'"
                    class="flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                    <span :class="selectedFuel === 'gas' ? 'block' : 'hidden'"
                        class="w-2 h-2 bg-white rounded-full"></span>
                </span>
                Gas
            </label>
        </div>

        <!-- Hour Tracking + Overage Rate -->
        <div class="flex items-center gap-4">
            <label for="hour_tracking"
                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-400 cursor-pointer">
                <div class="relative">
                    {!! html()->checkbox('hour_tracking', true)->id('hour_tracking')->class('sr-only')->attribute('@change', 'hourTracking = !hourTracking') !!}
                    <div :class="hourTracking ? 'border-blue-500 bg-blue-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                        class="mr-2 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                        <span :class="hourTracking ? '' : 'opacity-0'">
                            <x-heroicon-o-check class="w-3.5 h-3.5 text-white" />
                        </span>
                    </div>
                </div>
                Hour Tracking
            </label>

            {!! html()->text('overage_rate')->attributes([
                    'placeholder' => 'Overage Rate / Hr.',
                    'class' =>
                        'w-44 rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                ]) !!}
        </div>
    </div>

    <!-- Sale Prices -->
    <div>
        <h4 class="text-sm font-semibold text-gray-700 dark:text-white mb-2">Sale Prices</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach (['daily' => 'Daily', 'weekend' => 'Weekend Spcl.', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
                    {!! html()->text("sale_prices[$key]")->attributes([
                            'placeholder' => '$ 0',
                            'class' =>
                                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            @endforeach
        </div>
    </div>

    <!-- Delivery Options -->
    <div>
        <h4 class="text-sm font-semibold text-gray-700 dark:text-white mb-2">Pickup & Delivery</h4>

        <div class="flex flex-col sm:flex-row gap-6 mb-3">
            <!-- In Store Pickup -->
            <div x-data="{ checked: {{ old('pickup_instore') ? 'true' : 'false' }} }">
                <label for="pickup_instore"
                    class="flex items-center text-sm font-medium text-gray-700 cursor-pointer select-none dark:text-gray-400">
                    <div class="relative">
                        {!! html()->checkbox('pickup_instore')->id('pickup_instore')->class('sr-only')->attribute('@change', 'checked = !checked') !!}

                        <div :class="checked ? 'border-blue-500 bg-blue-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                            class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                            <span :class="checked ? '' : 'opacity-0'">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white"
                                        stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                    </div>
                    In Store Pick Up
                </label>
            </div>

            <!-- Delivery and Pickup -->
            <div x-data="{ checked: {{ old('delivery_option') ? 'true' : 'false' }} }">
                <label for="delivery_option"
                    class="flex items-center text-sm font-medium text-gray-700 cursor-pointer select-none dark:text-gray-400">
                    <div class="relative">
                        {!! html()->checkbox('delivery_option')->id('delivery_option')->class('sr-only')->attribute('@change', 'checked = !checked') !!}

                        <div :class="checked ? 'border-blue-500 bg-blue-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                            class="mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                            <span :class="checked ? '' : 'opacity-0'">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white"
                                        stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                    </div>
                    Delivery and Pickup
                </label>
            </div>
        </div>


        @php
            $standardMiles = $deliverySettings['standard_miles'] ?? 15;
            $extendedMiles = $deliverySettings['extended_miles'] ?? 30;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Standard Delivery Fee & Distance -->
            <div>
                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">
                    Std. Delivery Fee
                    <a href="#" class="text-blue-600 hover:underline text-xs ml-1">Update Delivery Range</a>
                </label>
                <div class="flex items-center gap-2">
                    {!! html()->text('std_delivery_fee')->attributes([
                            'placeholder' => '$ 0',
                            'class' =>
                                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                    {!! html()->text('std_delivery_miles')->value($standardMiles)->attributes([
                            'readonly' => true,
                            'class' =>
                                'w-16 text-center bg-gray-100 dark:bg-gray-700 border border-gray-300 rounded px-2 py-2 text-sm text-gray-600 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Extended Delivery Fee & Distance -->
            <div>
                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Extended Delivery Fee</label>
                <div class="flex items-center gap-2">
                    {!! html()->text('ext_delivery_fee')->attributes([
                            'placeholder' => '$ 0',
                            'class' =>
                                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                    {!! html()->text('ext_delivery_miles')->value($extendedMiles)->attributes([
                            'readonly' => true,
                            'class' =>
                                'w-16 text-center bg-gray-100 dark:bg-gray-700 border border-gray-300 rounded px-2 py-2 text-sm text-gray-600 dark:text-white',
                        ]) !!}
                </div>
            </div>
        </div>


    </div>

    <p class="text-xs text-gray-500 italic mt-4">
        <a href="#" class="text-blue-600 hover:underline">
            Update Settings: Damage Waiver, Cleaning, Fuel Messages, Preselection Options, & Fuel Settings
        </a>
    </p>
</div>

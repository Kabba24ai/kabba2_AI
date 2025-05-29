<div class="grid grid-cols-1 lg:grid-cols-2 gap-1">
    <!-- Rental Configuration -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Rental Configuration</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Daily -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Daily</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('rental_prices[daily]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">8 Hrs Allocated</p>
            </div>

            <!-- Weekend Spcl. -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weekend Spcl.</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('rental_prices[weekend]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">14 Hrs Allocated</p>
            </div>

            <!-- Weekly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weekly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('rental_prices[weekly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">40 Hrs Allocated</p>
            </div>

            <!-- Monthly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('rental_prices[monthly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <p class="text-xs text-gray-500 mt-1">160 Hrs Allocated</p>
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
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('damage_waivers[monthly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('damage_waivers[monthly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('damage_waivers[monthly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Damage Waiver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 whitespace-nowrap">Damage
                    Waiver</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('damage_waivers[monthly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
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
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('prepaid_cleaning')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Prepaid Fuel -->
            <div>
                <label class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prepaid
                    Fuel</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('prepaid_fuel')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Gallons + Fuel Type -->
            <div>
                <label
                    class="whitespace-nowrap block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gallons</label>
                {!! html()->text('fuel_gallons')->attributes([
                        'placeholder' => '0',
                        'class' =>
                            'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
                <div class="flex items-center gap-4 mt-2">
                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                        {!! html()->radio('fuel_type', false, 'diesel')->id('fuel_diesel')->class('mr-1') !!} Diesel
                    </label>
                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                        {!! html()->radio('fuel_type', false, 'gas')->id('fuel_gas')->class('mr-1') !!} Gas
                    </label>
                </div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <p class="text-xs">
            <a href="#" class="text-blue-500 hover:underline font-medium">Update Settings:</a>
            <span class="italic text-gray-700 dark:text-gray-300">
                Damage Waiver, Cleaning, Fuel Messages, Preselection Options &amp; Fuel
            </span>
        </p>

    </div>

    <!-- Sale Prices -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-2">
        <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white mb-4">Sale Prices</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Daily -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Daily</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('sale_prices[daily]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Weekend Spcl. -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weekend Spcl.</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('sale_prices[weekend]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Weekly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Weekly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('sale_prices[weekly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>

            <!-- Monthly -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly</label>
                <div class="flex items-center gap-1">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('sale_prices[monthly]')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Delivery Fee Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-4 mt-4 items-end">
            <!-- Standard Delivery Fee -->
            <div class="flex flex-col items-center">
                <label class="block w-full text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-left">Delivery
                    Fee</label>
                <div class="flex items-center gap-1 w-full">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('std_delivery_fee')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <span
                    class="mt-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 border rounded text-gray-600 dark:text-white text-center">
                    Mi 15
                </span>
            </div>

            <!-- Extended Delivery Fee -->
            <div class="flex flex-col items-center">
                <label class="block w-full text-sm font-medium text-transparent mb-1 select-none">-</label>
                <div class="flex items-center gap-1 w-full">
                    <span class="text-gray-500 text-sm">$</span>
                    {!! html()->text('ext_delivery_fee')->attributes([
                            'placeholder' => '0',
                            'class' =>
                                'w-full rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        ]) !!}
                </div>
                <span
                    class="mt-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 border rounded text-gray-600 dark:text-white text-center">
                    Mi 30
                </span>
            </div>

            <!-- Update Delivery Range Link -->
            <div class="flex items-baseline-last justify-start h-full">
                <a href="#" class="text-xs py-2 text-blue-600 hover:underline whitespace-nowrap">Update Delivery
                    Range</a>
            </div>

            <!-- Spacer -->
            <div></div>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Pickup Options -->
        <div class="flex flex-wrap items-center gap-6">
            <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                {!! html()->checkbox('pickup_instore', true)->class('mr-2') !!}
                In Store Pick Up
            </label>

            <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                {!! html()->checkbox('delivery_option', true)->class('mr-2') !!}
                Delivery and Pickup
            </label>
        </div>

        <!-- Spacer -->
        <div class="h-3 md:h-4"></div>

        <!-- Hour Tracking + Overage Rate -->
        <div class="flex flex-wrap items-center gap-4 mt-6">
            <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                {!! html()->checkbox('hour_tracking', true)->class('mr-2') !!}
                Hour Tracking
            </label>

            <div class="flex items-center gap-1">
                <span class="text-gray-500 text-sm">$</span>
                {!! html()->text('overage_rate')->attributes([
                        'placeholder' => '0',
                        'class' =>
                            'w-24 rounded-lg border px-2 py-1 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    ]) !!}
            </div>

            <span class="text-sm text-gray-600 dark:text-gray-300">Overage Rate / Hr.</span>
        </div>

    </div>
</div>

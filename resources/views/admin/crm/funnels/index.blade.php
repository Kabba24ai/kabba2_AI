@extends('admin.layouts.app')

@section('title', 'Default Funnels')

@push('css')
@endpush

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-bold text-gray-900">Default Funnels</h3>
    </div>

    @include('flash::message')

    <div class="grid grid-cols-1 lg:grid-cols-1 gap-4">
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-6 py-6">

                    {{ html()->modelForm($defaultFunnels, 'POST')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                    <!-- ===================== -->
                    <!--       TAB HEADERS     -->
                    <!-- ===================== -->
                    <div x-data="{ tab: 'rental_delivery' }">

                        <div class="border-b border-gray-200 dark:border-gray-800 mb-4">
                            <nav class="flex space-x-6">

                                <button type="button" @click="tab = 'rental_delivery'"
                                    :class="tab === 'rental_delivery' ? 'text-brand-600 border-b-2 border-brand-600' :
                                        'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental Delivery
                                </button>

                                <button type="button" @click="tab = 'rental_return'"
                                    :class="tab === 'rental_return' ? 'text-brand-600 border-b-2 border-brand-600' :
                                        'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental Return
                                </button>

                                <button type="button" @click="tab = 'cod'"
                                    :class="tab === 'cod' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    COD Orders
                                </button>

                            </nav>
                        </div>

                        <!-- ===================== -->
                        <!--     COD ORDERS TAB    -->
                        <!-- ===================== -->
                        <div x-show="tab === 'cod'" x-cloak>
                            <div
                                class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Cash on Delivery Order Message - Text Message sent after cod order placed
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="truck_delivery_cod_order_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            Truck Delivery COD Order Message
                                        </label>
                                        <label for="truck_delivery_cod_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'truck_delivery_cod_message_enabled',
                                                    old('truck_delivery_cod_message_enabled', $defaultFunnels['truck_delivery_cod_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('truck_delivery_cod_order_message', old('truck_delivery_cod_order_message', $defaultFunnels['truck_delivery_cod_order_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('truck_delivery_cod_order_message'),
                                            'border-red-500' => $errors->has('truck_delivery_cod_order_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter here',
                                            'autocomplete' => 'off',
                                            'id' => 'truck_delivery_cod_order_message',
                                        ])->required() !!}
                                    @error('truck_delivery_cod_order_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="truck_delivery_cod_order_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="store_delivery_cod_order_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                            Store Delivery COD Order Message
                                        </label>
                                        <label for="store_delivery_cod_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'store_delivery_cod_message_enabled',
                                                    old('store_delivery_cod_message_enabled', $defaultFunnels['store_delivery_cod_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('store_delivery_cod_order_message', old('store_delivery_cod_order_message', $defaultFunnels['store_delivery_cod_order_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('store_delivery_cod_order_message'),
                                            'border-red-500' => $errors->has('store_delivery_cod_order_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter here',
                                            'autocomplete' => 'off',
                                            'id' => 'store_delivery_cod_order_message',
                                        ])->required() !!}
                                    @error('store_delivery_cod_order_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="store_delivery_cod_order_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                            </div>
                        </div>

                        <!-- ===================== -->
                        <!--   RENTAL Delivery TAB    -->
                        <!-- ===================== -->
                        <div x-show="tab === 'rental_delivery'" x-cloak>

                            <div>
                                <div
                                    class="flex items-center  mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Rental Message - Day Before Delivery Date - Text Message sent at 3:00 PM
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-2">
                                            <label for="rental_delivery_day_before_truck_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                                Truck Delivery Message
                                            </label>
                                            <label for="rental_delivery_day_before_truck_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_delivery_day_before_truck_message_enabled',
                                                        old(
                                                            'rental_delivery_day_before_truck_message_enabled',
                                                            $defaultFunnels['rental_delivery_day_before_truck_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_delivery_day_before_truck_message',
                                                old('rental_delivery_day_before_truck_message', $defaultFunnels['rental_delivery_day_before_truck_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_delivery_day_before_truck_message'),
                                                'border-red-500' => $errors->has('rental_delivery_day_before_truck_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter here',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_delivery_day_before_truck_message',
                                            ])->required() !!}
                                        @error('rental_delivery_day_before_truck_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_delivery_day_before_truck_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center mb-2 gap-1">
                                            <label for="rental_delivery_day_before_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Return Message
                                            </label>
                                            <label for="rental_delivery_day_before_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_delivery_day_before_store_message_enabled',
                                                        old(
                                                            'rental_delivery_day_before_store_message_enabled',
                                                            $defaultFunnels['rental_delivery_day_before_store_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_delivery_day_before_store_message',
                                                old('rental_delivery_day_before_store_message', $defaultFunnels['rental_delivery_day_before_store_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_delivery_day_before_store_message'),
                                                'border-red-500' => $errors->has('rental_delivery_day_before_store_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_delivery_day_before_store_message',
                                            ])->required() !!}
                                        @error('rental_delivery_day_before_store_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_delivery_day_before_store_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex items-center justify-between mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Rental Message - Morning of Delivery Date - Text Message sent at 7:00 AM
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-2">
                                            <label for="rental_delivery_same_day_truck_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                                Truck Pickup Message
                                            </label>
                                            <label for="rental_delivery_same_day_truck_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_delivery_same_day_truck_message_enabled',
                                                        old(
                                                            'rental_delivery_same_day_truck_message_enabled',
                                                            $defaultFunnels['rental_delivery_same_day_truck_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_delivery_same_day_truck_message',
                                                old('rental_delivery_same_day_truck_message', $defaultFunnels['rental_delivery_same_day_truck_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_delivery_same_day_truck_message'),
                                                'border-red-500' => $errors->has('rental_delivery_same_day_truck_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_delivery_same_day_truck_message',
                                            ])->required() !!}
                                        @error('rental_delivery_same_day_truck_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_delivery_same_day_truck_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>

                                    <div>
                                        <div class="flex items-center mb-2 gap-1">
                                            <label for="rental_same_day_store_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Return Message
                                            </label>
                                            <label for="rental_delivery_same_day_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_delivery_same_day_store_message_enabled',
                                                        old(
                                                            'rental_delivery_same_day_store_message_enabled',
                                                            $defaultFunnels['rental_delivery_same_day_store_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_delivery_same_day_store_message',
                                                old('rental_delivery_same_day_store_message', $defaultFunnels['rental_delivery_same_day_store_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_delivery_same_day_store_message'),
                                                'border-red-500' => $errors->has('rental_delivery_same_day_store_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_delivery_same_day_store_message',
                                            ])->required() !!}
                                        @error('rental_delivery_same_day_store_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_delivery_same_day_store_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===================== -->
                        <!--   RENTAL RETURN TAB      -->
                        <!-- ===================== -->
                        <div x-show="tab === 'rental_return'" x-cloak>

                            <div>
                                <div
                                    class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Rental Message - Day Before Return Date - Text Message sent at 3:00 PM
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-2">
                                            <label for="rental_return_day_before_truck_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                                Truck Return Message
                                            </label>
                                            <label for="rental_return_day_before_truck_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_return_day_before_truck_message_enabled',
                                                        old(
                                                            'rental_return_day_before_truck_message_enabled',
                                                            $defaultFunnels['rental_return_day_before_truck_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_return_day_before_truck_message',
                                                old('rental_return_day_before_truck_message', $defaultFunnels['rental_return_day_before_truck_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_return_day_before_truck_message'),
                                                'border-red-500' => $errors->has('rental_return_day_before_truck_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter here',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_return_day_before_truck_message',
                                            ])->required() !!}
                                        @error('rental_return_day_before_truck_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_return_day_before_truck_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center mb-2 gap-1">
                                            <label for="rental_return_day_before_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Return Message
                                            </label>
                                            <label for="rental_return_day_before_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_return_day_before_store_message_enabled',
                                                        old(
                                                            'rental_return_day_before_store_message_enabled',
                                                            $defaultFunnels['rental_return_day_before_store_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_return_day_before_store_message',
                                                old('rental_return_day_before_store_message', $defaultFunnels['rental_return_day_before_store_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_return_day_before_store_message'),
                                                'border-red-500' => $errors->has('rental_return_day_before_store_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_return_day_before_store_message',
                                            ])->required() !!}
                                        @error('rental_return_day_before_store_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_return_day_before_store_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        Rental Message - Morning of Return Date - Text Message sent at 7:00 AM
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-2">
                                            <label for="rental_same_day_truck_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                                Truck Pickup Message
                                            </label>
                                            <label for="rental_return_same_day_truck_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_return_same_day_truck_message_enabled',
                                                        old(
                                                            'rental_return_same_day_truck_message_enabled',
                                                            $defaultFunnels['rental_return_same_day_truck_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_return_same_day_truck_message',
                                                old('rental_return_same_day_truck_message', $defaultFunnels['rental_return_same_day_truck_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_return_same_day_truck_message'),
                                                'border-red-500' => $errors->has('rental_return_same_day_truck_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_return_same_day_truck_message',
                                            ])->required() !!}
                                        @error('rental_return_same_day_truck_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_return_same_day_truck_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>

                                    <div>
                                        <div class="flex items-center mb-2 gap-1">
                                            <label for="rental_same_day_store_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Return Message
                                            </label>
                                            <label for="rental_return_same_day_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_return_same_day_store_message_enabled',
                                                        old(
                                                            'rental_return_same_day_store_message_enabled',
                                                            $defaultFunnels['rental_return_same_day_store_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_return_same_day_store_message',
                                                old('rental_return_same_day_store_message', $defaultFunnels['rental_return_same_day_store_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_return_same_day_store_message'),
                                                'border-red-500' => $errors->has('rental_return_same_day_store_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_return_same_day_store_message',
                                            ])->required() !!}
                                        @error('rental_return_same_day_store_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_return_same_day_store_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="flex justify-end mt-8 space-x-4">
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                            Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
                        </button>
                    </div>

                    {{ html()->form()->close() }}

                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll("textarea[maxlength]").forEach((t) => {
                let c = document.getElementById(t.id + "_count");
                if (!c) return;
                const update = () => c.textContent = t.value.length + "/500";
                t.addEventListener("input", update);
                update();
            });
        });
    </script>
@endpush

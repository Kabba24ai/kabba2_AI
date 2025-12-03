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
                    <div x-data="{ tab: 'rental_start' }">

                        <div class="border-b border-gray-200 dark:border-gray-800 mb-4">
                            <nav class="flex space-x-6">

                                <button type="button" @click="tab = 'rental_start'"
                                    :class="tab === 'rental_start' ? 'text-brand-600 border-b-2 border-brand-600' :
                                        'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental Start
                                </button>

                                <button type="button" @click="tab = 'rental_end'"
                                    :class="tab === 'rental_end' ? 'text-brand-600 border-b-2 border-brand-600' :
                                        'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental End
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
                                class="flex items-center justify-between mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Cash on Delivery Order Message
                                </span>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Text Message sent after cod order placed
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="cod_order_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            COD Order Message
                                        </label>
                                        <label for="cod_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'cod_message_enabled',
                                                    old('cod_message_enabled', $defaultFunnels['cod_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('cod_order_message', old('cod_order_message', $defaultFunnels['cod_order_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('cod_order_message'),
                                            'border-red-500' => $errors->has('cod_order_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter here',
                                            'autocomplete' => 'off',
                                            'id' => 'cod_order_message',
                                        ])->required() !!}
                                    @error('cod_order_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="cod_order_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>

                            </div>
                        </div>

                        <!-- ===================== -->
                        <!--   RENTAL START TAB    -->
                        <!-- ===================== -->
                        <div x-show="tab === 'rental_start'" x-cloak>

                            <div>
                                <div
                                    class="flex items-center justify-between mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Rental Message -
                                        Day
                                        Before Due Date</span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Text Message sent
                                        at
                                        3:00 PM</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-2">
                                            <label for="rental_day_before_truck_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                                Truck Pickup Message
                                            </label>
                                            <label for="rental_day_before_truck_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_day_before_truck_message_enabled',
                                                        old(
                                                            'rental_day_before_truck_message_enabled',
                                                            $defaultFunnels['rental_day_before_truck_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_day_before_truck_message',
                                                old('rental_day_before_truck_message', $defaultFunnels['rental_day_before_truck_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_day_before_truck_message'),
                                                'border-red-500' => $errors->has('rental_day_before_truck_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter here',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_day_before_truck_message',
                                            ])->required() !!}
                                        @error('rental_day_before_truck_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_day_before_truck_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex items-center justify-between mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Rental Message -
                                        Morning of Due Date</span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Text Message sent
                                        at
                                        7:00 AM</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-2">
                                            <label for="rental_same_day_truck_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                                Truck Pickup Message
                                            </label>
                                            <label for="rental_same_day_truck_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_same_day_truck_message_enabled',
                                                        old(
                                                            'rental_same_day_truck_message_enabled',
                                                            $defaultFunnels['rental_same_day_truck_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_same_day_truck_message',
                                                old('rental_same_day_truck_message', $defaultFunnels['rental_same_day_truck_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_same_day_truck_message'),
                                                'border-red-500' => $errors->has('rental_same_day_truck_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_same_day_truck_message',
                                            ])->required() !!}
                                        @error('rental_same_day_truck_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_same_day_truck_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===================== -->
                        <!--   RENTAL END TAB      -->
                        <!-- ===================== -->
                        <div x-show="tab === 'rental_end'" x-cloak>

                            <div>
                                <div
                                    class="flex items-center justify-between mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Rental Message -
                                        Day
                                        Before Due Date</span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Text Message sent
                                        at
                                        3:00 PM</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center mb-2 gap-1">
                                            <label for="rental_day_before_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Return Message
                                            </label>
                                            <label for="rental_day_before_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_day_before_store_message_enabled',
                                                        old(
                                                            'rental_day_before_store_message_enabled',
                                                            $defaultFunnels['rental_day_before_store_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_day_before_store_message',
                                                old('rental_day_before_store_message', $defaultFunnels['rental_day_before_store_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_day_before_store_message'),
                                                'border-red-500' => $errors->has('rental_day_before_store_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_day_before_store_message',
                                            ])->required() !!}
                                        @error('rental_day_before_store_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_day_before_store_message_count"
                                            class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex items-center justify-between mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Rental Message -
                                        Morning of Due Date</span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Text Message sent
                                        at
                                        7:00 AM</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    <div>
                                        <div class="flex items-center mb-2 gap-1">
                                            <label for="rental_same_day_store_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Return Message
                                            </label>
                                            <label for="rental_same_day_store_message_enabled"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {!! html()->checkbox(
                                                        'rental_same_day_store_message_enabled',
                                                        old(
                                                            'rental_same_day_store_message_enabled',
                                                            $defaultFunnels['rental_same_day_store_message_enabled'] ?? null,
                                                        ) == '1',
                                                        '1',
                                                    )->class([
                                                        'mr-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                    ]) !!}
                                                Active
                                            </label>
                                        </div>
                                        {!! html()->textarea(
                                                'rental_same_day_store_message',
                                                old('rental_same_day_store_message', $defaultFunnels['rental_same_day_store_message'] ?? null),
                                            )->class([
                                                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                                'border-gray-300' => !$errors->has('rental_same_day_store_message'),
                                                'border-red-500' => $errors->has('rental_same_day_store_message'),
                                            ])->attributes([
                                                'rows' => 5,
                                                'maxlength' => 500,
                                                'data-parsley-maxlength' => 500,
                                                'placeholder' => 'Enter store name',
                                                'autocomplete' => 'off',
                                                'id' => 'rental_same_day_store_message',
                                            ])->required() !!}
                                        @error('rental_same_day_store_message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <span id="rental_same_day_store_message_count"
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

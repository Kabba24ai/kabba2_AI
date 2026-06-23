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
                    <div x-data="{
                                    tab: localStorage.getItem('funnels_tab') || 'rental_delivery_paid',
                                    setTab(value) {
                                        this.tab = value;
                                        localStorage.setItem('funnels_tab', value);
                                    }
                                }">

                        <div class="border-b border-gray-200 dark:border-gray-800 mb-4">
                            <nav class="flex space-x-6">

                                <button type="button" @click="setTab('rental_delivery_paid')"
                                    :class="tab === 'rental_delivery_paid' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental Delivery &mdash; Paid
                                </button>

                                <button type="button" @click="setTab('rental_delivery_pod')"
                                    :class="tab === 'rental_delivery_pod' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental Delivery &mdash; POD
                                </button>

                                <button type="button" @click="setTab('rental_return')"
                                    :class="tab === 'rental_return' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-gray-500'"
                                    class="py-2 text-sm font-semibold">
                                    Rental Return
                                </button>

                            </nav>
                        </div>

                        <!-- ===================== -->
                        <!--     ORDERS TAB    -->
                        <!-- ===================== -->
                        <div x-show="tab === 'rental_delivery_pod'" x-cloak>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                Available merge codes:
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;customer_name&#125;&#125;</code>
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;delivery_date&#125;&#125;</code>
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;payment_link&#125;&#125;</code>
                            </p>
                            <div
                                class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    POD Order Confirmation — Text Message sent immediately after a Pay-on-Delivery order is placed
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="truck_delivery_cod_order_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            Truck Delivery POD Order Message
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
                                            Store Delivery POD Order Message
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
                            <!-- ===================== -->
                            <!-- POD PAYMENT LINK MESSAGE (1 min after confirmation) -->
                            <!-- ===================== -->
                            <div
                                class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    POD Payment Link Message — Sent 1 Minute after the Order Confirmation Message
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="pod_payment_link_truck_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            Truck Delivery POD Payment Link Message
                                        </label>
                                        <label for="pod_payment_link_truck_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'pod_payment_link_truck_message_enabled',
                                                    old('pod_payment_link_truck_message_enabled', $defaultFunnels['pod_payment_link_truck_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('pod_payment_link_truck_message', old('pod_payment_link_truck_message', $defaultFunnels['pod_payment_link_truck_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('pod_payment_link_truck_message'),
                                            'border-red-500' => $errors->has('pod_payment_link_truck_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter message with {{payment_link}}',
                                            'autocomplete' => 'off',
                                            'id' => 'pod_payment_link_truck_message',
                                        ]) !!}
                                    @error('pod_payment_link_truck_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="pod_payment_link_truck_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="pod_payment_link_store_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                            Store Pickup POD Payment Link Message
                                        </label>
                                        <label for="pod_payment_link_store_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'pod_payment_link_store_message_enabled',
                                                    old('pod_payment_link_store_message_enabled', $defaultFunnels['pod_payment_link_store_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('pod_payment_link_store_message', old('pod_payment_link_store_message', $defaultFunnels['pod_payment_link_store_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('pod_payment_link_store_message'),
                                            'border-red-500' => $errors->has('pod_payment_link_store_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter message with {{store_name}} and {{payment_link}}',
                                            'autocomplete' => 'off',
                                            'id' => 'pod_payment_link_store_message',
                                        ]) !!}
                                    @error('pod_payment_link_store_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="pod_payment_link_store_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                            </div>

                            <div
                                class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    POD Same-Day Reminder — Sent at 7:00 AM on the day of scheduled delivery (if still unpaid)
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="truck_delivery_same_day_cod_order_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            Truck Delivery POD Order Message
                                        </label>
                                        <label for="truck_delivery_same_day_cod_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'truck_delivery_same_day_cod_message_enabled',
                                                    old('truck_delivery_same_day_cod_message_enabled', $defaultFunnels['truck_delivery_same_day_cod_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('truck_delivery_same_day_cod_order_message', old('truck_delivery_same_day_cod_order_message', $defaultFunnels['truck_delivery_same_day_cod_order_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('truck_delivery_same_day_cod_order_message'),
                                            'border-red-500' => $errors->has('truck_delivery_same_day_cod_order_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter here',
                                            'autocomplete' => 'off',
                                            'id' => 'truck_delivery_same_day_cod_order_message',
                                        ])->required() !!}
                                    @error('truck_delivery_same_day_cod_order_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="truck_delivery_same_day_cod_order_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="store_delivery_same_day_cod_order_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                            Store Delivery POD Order Message
                                        </label>
                                        <label for="store_delivery_same_day_cod_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'store_delivery_same_day_cod_message_enabled',
                                                    old('store_delivery_same_day_cod_message_enabled', $defaultFunnels['store_delivery_same_day_cod_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('store_delivery_same_day_cod_order_message', old('store_delivery_same_day_cod_order_message', $defaultFunnels['store_delivery_same_day_cod_order_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('store_delivery_same_day_cod_order_message'),
                                            'border-red-500' => $errors->has('store_delivery_same_day_cod_order_message'),
                                        ])->attributes([
                                            'rows' => 5,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter here',
                                            'autocomplete' => 'off',
                                            'id' => 'store_delivery_same_day_cod_order_message',
                                        ])->required() !!}
                                    @error('store_delivery_same_day_cod_order_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="store_delivery_same_day_cod_order_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                            </div>
                            <!-- ===================== -->
                            <!-- POD FINAL RENTAL REMINDER — 9 AM -->
                            <!-- ===================== -->
                            <div
                                class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    POD Final Rental Reminder — Sent at 9:00 AM on the day of the scheduled delivery (if still unpaid)
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="pod_final_reminder_truck_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            Truck Delivery POD Order Message
                                        </label>
                                        <label for="pod_final_reminder_truck_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'pod_final_reminder_truck_message_enabled',
                                                    old('pod_final_reminder_truck_message_enabled', $defaultFunnels['pod_final_reminder_truck_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('pod_final_reminder_truck_message', old('pod_final_reminder_truck_message', $defaultFunnels['pod_final_reminder_truck_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('pod_final_reminder_truck_message'),
                                            'border-red-500' => $errors->has('pod_final_reminder_truck_message'),
                                        ])->attributes([
                                            'rows' => 8,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter message with {{payment_link}}',
                                            'autocomplete' => 'off',
                                            'id' => 'pod_final_reminder_truck_message',
                                        ]) !!}
                                    @error('pod_final_reminder_truck_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="pod_final_reminder_truck_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="pod_final_reminder_store_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                            Store Delivery POD Order Message
                                        </label>
                                        <label for="pod_final_reminder_store_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'pod_final_reminder_store_message_enabled',
                                                    old('pod_final_reminder_store_message_enabled', $defaultFunnels['pod_final_reminder_store_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('pod_final_reminder_store_message', old('pod_final_reminder_store_message', $defaultFunnels['pod_final_reminder_store_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('pod_final_reminder_store_message'),
                                            'border-red-500' => $errors->has('pod_final_reminder_store_message'),
                                        ])->attributes([
                                            'rows' => 8,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter message with {{payment_link}}',
                                            'autocomplete' => 'off',
                                            'id' => 'pod_final_reminder_store_message',
                                        ]) !!}
                                    @error('pod_final_reminder_store_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="pod_final_reminder_store_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                            </div>

                            <!-- ===================== -->
                            <!-- POD LAST DITCH RECOVERY — 4 PM -->
                            <!-- ===================== -->
                            <div
                                class="flex items-center mb-2 border-b border-gray-200 dark:border-gray-700 my-4">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    POD Last Ditch Recovery Message — Sent at 4:00 PM the day of the scheduled delivery (if still unpaid)
                                </span>
                            </div>
                            <div class="grid grid-cols-1 grid-flow-col md:grid-cols-2 gap-6">
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="pod_last_ditch_truck_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-truck class="w-5 h-5 text-yellow-600 mr-1" />
                                            Truck Delivery POD Order Message
                                        </label>
                                        <label for="pod_last_ditch_truck_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'pod_last_ditch_truck_message_enabled',
                                                    old('pod_last_ditch_truck_message_enabled', $defaultFunnels['pod_last_ditch_truck_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('pod_last_ditch_truck_message', old('pod_last_ditch_truck_message', $defaultFunnels['pod_last_ditch_truck_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('pod_last_ditch_truck_message'),
                                            'border-red-500' => $errors->has('pod_last_ditch_truck_message'),
                                        ])->attributes([
                                            'rows' => 10,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter message with {{payment_link}}',
                                            'autocomplete' => 'off',
                                            'id' => 'pod_last_ditch_truck_message',
                                        ]) !!}
                                    @error('pod_last_ditch_truck_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="pod_last_ditch_truck_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                                <div class="cols-span-1">
                                    <div class="flex items-center mb-2 gap-2">
                                        <label for="pod_last_ditch_store_message"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                            <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                            Store Delivery POD Order Message
                                        </label>
                                        <label for="pod_last_ditch_store_message_enabled"
                                            class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {!! html()->checkbox(
                                                    'pod_last_ditch_store_message_enabled',
                                                    old('pod_last_ditch_store_message_enabled', $defaultFunnels['pod_last_ditch_store_message_enabled'] ?? null) == '1',
                                                    '1',
                                                )->class([
                                                    'mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700 dark:focus:ring-blue-500',
                                                ]) !!}
                                            Active
                                        </label>
                                    </div>
                                    {!! html()->textarea('pod_last_ditch_store_message', old('pod_last_ditch_store_message', $defaultFunnels['pod_last_ditch_store_message'] ?? null))->class([
                                            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                                            'border-gray-300' => !$errors->has('pod_last_ditch_store_message'),
                                            'border-red-500' => $errors->has('pod_last_ditch_store_message'),
                                        ])->attributes([
                                            'rows' => 10,
                                            'maxlength' => 500,
                                            'data-parsley-maxlength' => 500,
                                            'placeholder' => 'Enter message with {{payment_link}}',
                                            'autocomplete' => 'off',
                                            'id' => 'pod_last_ditch_store_message',
                                        ]) !!}
                                    @error('pod_last_ditch_store_message')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <span id="pod_last_ditch_store_message_count"
                                        class="text-sm text-gray-500 dark:text-gray-400 float-right">0/500</span>
                                </div>
                            </div>
                        </div>

                        <!-- ===================== -->
                        <!--   RENTAL Delivery - Paid TAB    -->
                        <!-- ===================== -->
                        <div x-show="tab === 'rental_delivery_paid'" x-cloak>

                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                Available merge codes:
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;customer_name&#125;&#125;</code>
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;store_name&#125;&#125;</code>
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;delivery_date&#125;&#125;</code>
                            </p>

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
                                                Store Delivery Message
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
                                                Truck Delivery Message
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
                                            <label for="rental_delivery_same_day_store_message"
                                                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 required">
                                                <x-heroicon-o-building-storefront class="w-5 h-5 text-yellow-600 mr-1" />
                                                Store Delivery Message
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

                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                Available merge codes:
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;customer_name&#125;&#125;</code>
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;store_name&#125;&#125;</code>
                                <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs font-mono">&#123;&#123;return_date&#125;&#125;</code>
                            </p>

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

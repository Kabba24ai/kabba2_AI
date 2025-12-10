{{-- Equipment Form --}}
<div class="space-y-6">
    {{-- First Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Basic Information --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <x-heroicon-o-document-text class="h-5 w-5 text-blue-600" />
                <h3 class="text-lg font-bold text-gray-900">Basic Information</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="equipment_name" class="block text-sm font-medium text-gray-700 mb-1 required">
                        Equipment Name
                    </label>
                    {!! html()->text('equipment_name')->class([
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('equipment_name'),
                            'border-red-500' => $errors->has('equipment_name'),
                        ])->attributes([
                            'maxlength' => 240,
                            'data-parsley-maxlength' => 240,
                            'placeholder' => 'Enter Equipment Name',
                            'autocomplete' => 'off',
                        ])->required() !!}
                    @error('equipment_name')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="product_category_id" class="block text-sm font-medium text-gray-700 mb-1 required">
                        Category
                    </label>
                    {!! html()->select('product_category_id', ['' => 'Select Category'] + $categories)->class([
                            'choices-select w-full px-2 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                            'border-red-300 bg-red-50' => $errors->has('product_category_id'),
                            'border-gray-300' => !$errors->has('product_category_id'),
                        ])->attributes([
                            'data-parsley-errors-container' => '#product_category_id-errors',
                        ])->required() !!}
                    <div id="product_category_id-errors"></div>
                    @error('product_category_id')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between items-center ">
                        <div>
                            <label for="equipment_hours" class="block text-sm font-medium text-gray-700 mb-1">
                                Equipment Hours
                            </label>
                        </div>

                    </div>

                    {!! html()->text('equipment_hours')->class([
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('equipment_hours'),
                            'border-red-500' => $errors->has('equipment_hours'),
                        ])->attributes([
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'placeholder' => 'Enter Equipment Hours',
                            'autocomplete' => 'off',
                        ]) !!}
                    @error('equipment_hours')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @error('is_tracked')
                        <span id="is-tracked-error" class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between items-center ">
                        <label for="overage_rate" class="block text-sm font-medium text-gray-700 mb-1">
                            Overage Rate
                        </label>
                         <div>
                            {!! html()->checkbox('is_tracked', true)->id('is_tracked')->checked(old('is_tracked', isset($equipment) ? ($equipment->is_tracked === 'Yes' ? true : false) : true))->class('mr-2') !!}

                            <label for="is_tracked" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tracked
                            </label>
                        </div>
                    </div>
                    {!! html()->text('overage_rate')->class([
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('overage_rate'),
                            'border-red-500' => $errors->has('overage_rate'),
                        ])->attributes([
                            'data-numeric-input' => 'true',
                            'data-parsley-maxlength' => 8,
                            'maxlength' => 8,
                            'placeholder' => 'Enter Overage Rate',
                            'autocomplete' => 'off',
                            old('is_tracked', isset($equipment) ? ($equipment->is_tracked === 'Yes' ? false : true) : false) ? 'readonly' : null,
                        ]) !!}
                    @error('overage_rate')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="equipment_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Equipment ID <span class="text-red-500">*</span>
                    </label>
                    {!! html()->text('equipment_id')->class([
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('equipment_id'),
                            'border-red-500' => $errors->has('equipment_id'),
                        ])->attributes([
                            'maxlength' => 240,
                            'data-parsley-maxlength' => 240,
                            'placeholder' => 'EXC-001',
                            'autocomplete' => 'off',
                        ])->required() !!}
                    @error('equipment_id')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="store_id" class="block text-sm font-medium text-gray-700 mb-1 required">
                        Store
                    </label>
                    {!! html()->select('store_id', ['' => 'Select Store'] + $stores)->class([
                            'choices-select w-full px-2 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                            'border-red-300 bg-red-50' => $errors->has('store_id'),
                            'border-gray-300' => !$errors->has('store_id'),
                        ])->attributes([
                            'data-parsley-errors-container' => '#store_id-errors',
                        ])->required() !!}
                    <div id="store_id-errors"></div>
                    @error('store_id')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Equipment Details --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <x-heroicon-o-truck class="h-5 w-5 text-green-600" />
                <h3 class="text-lg font-bold text-gray-900">Equipment Details</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-1 required">
                        Brand
                    </label>
                    {!! html()->text('brand')->class([
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('brand'),
                            'border-red-500' => $errors->has('brand'),
                        ])->attributes([
                            'maxlength' => 240,
                            'data-parsley-maxlength' => 240,
                            'placeholder' => 'EXC-001',
                            'autocomplete' => 'off',
                        ])->required() !!}
                    @error('brand')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    {!! html()->text('model')->class([
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('model'),
                            'border-red-500' => $errors->has('model'),
                        ])->attributes([
                            'maxlength' => 240,
                            'data-parsley-maxlength' => 240,
                            'placeholder' => '320',
                            'autocomplete' => 'off',
                        ]) !!}
                    @error('model')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="model_year" class="block text-sm font-medium text-gray-700 mb-1">Model Year</label>
                    {!! html()->number('model_year')->class([
                            'italic w-full px-3 py-3 text-sm text-gray-500 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('model_year'),
                            'border-red-500' => $errors->has('model_year'),

                        ])->attributes([
                            'min' => 1900,
                            'max' => date('Y') + 1,
                            'placeholder' => date('Y'),
                        ]) !!}
                    @error('model_year')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date_acquired" class="block text-sm font-medium text-gray-700 mb-1">
                        Date Acquired
                    </label>
                    {!! html()->text(
                            'date_acquired',
                            isset($equipment)
                                ? ($equipment->date_acquired
                                    ? \App\Helpers\CustomHelper::formatDate($equipment->date_acquired)
                                    : '')
                                : '',
                        )->class([
                            'datepicker w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-gray-300' => !$errors->has('date_acquired'),
                            'border-red-500' => $errors->has('date_acquired'),
                        ])->attributes([
                            'data-format' => config('app.date.js_date_format'),
                            'placeholder' => 'mm/dd/yyyy',
                        ]) !!}
                    @error('date_acquired')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="sm:col-span-2 flex items-center space-x-2 mt-4">
                <input
                    type="checkbox"
                    name="not_for_rent"
                    id="not_for_rent"
                    value="1"
                    class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    @checked(old('not_for_rent', $equipment->not_for_rent ?? false))
                >
                <label for="not_for_rent" class="text-sm font-medium text-gray-700">
                    Not for Rent
                </label>
            </div>

        </div>
    </div>

    {{-- Second Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Financial & Legal --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <x-heroicon-o-currency-dollar class="h-5 w-5 text-yellow-600" />
                <h3 class="text-lg font-bold text-gray-900">Financial & Legal</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="purchase_cost" class="block text-sm font-medium text-gray-700 mb-1">
                        Purchase Cost
                    </label>
                    <div class="relative">
                        <x-heroicon-o-currency-dollar
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4 pointer-events-none" />
                        {!! html()->text('purchase_cost')->class([
                                'w-full pl-9 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('purchase_cost'),
                                'border-red-500' => $errors->has('purchase_cost'),
                            ])->attributes([
                                'data-digit-input' => 'true',
                                'min' => 0,
                                'placeholder' => '0.00',
                                'step' => '0.01',
                            ]) !!}
                    </div>
                    @error('purchase_cost')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ownership_type" class="block text-sm font-medium text-gray-700 mb-1">Ownership
                        Type</label>
                    {!! html()->select('ownership_type', [
                            '' => 'Select',
                            'owned' => 'Owned',
                            'financed' => 'Financed',
                            'leased' => 'Leased',
                        ])->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                            'border-red-500' => $errors->has('ownership_type'),
                        ]) !!}
                    @error('ownership_type')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="finance_company" class="block text-sm font-medium text-gray-700 mb-1">Finance
                        Company</label>
                    {!! html()->text('finance_company')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('finance_company'),
                        ])->attributes([
                            'placeholder' => 'Bank/Lender',
                        ]) !!}
                    @error('finance_company')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="term_in_months" class="block text-sm font-medium text-gray-700 mb-1">Term
                        (Months)</label>
                    {!! html()->number('term_in_months')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('term_in_months'),
                        ])->attributes([
                            'min' => 1,
                            'placeholder' => '60',
                        ]) !!}
                    @error('term_in_months')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>


                <div>
                    <label for="interest_rate" class="block text-sm font-medium text-gray-700 mb-1">
                        Interest Rate (%)
                    </label>
                    {!! html()->number('interest_rate')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('interest_rate'),
                        ])->attributes([
                            'min' => 0,
                            'step' => 0.01,
                            'placeholder' => '5.25',
                        ]) !!}
                    @error('interest_rate')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="monthly_payment" class="block text-sm font-medium text-gray-700 mb-1">
                        Monthly Payment
                    </label>
                    <div class="relative">
                        <x-heroicon-o-currency-dollar
                            class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                        {!! html()->text('monthly_payment')->class([
                                'w-full pl-9 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('monthly_payment'),
                                'border-red-500' => $errors->has('monthly_payment'),
                            ])->attributes([
                                'data-digit-input' => 'true',
                                'min' => 0,
                                'placeholder' => '0.00',
                                'step' => '0.01',
                            ]) !!}
                        @error('monthly_payment')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>


        {{-- Identification Numbers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <x-heroicon-o-identification class="h-5 w-5 text-purple-600" />
                <h3 class="text-lg font-bold text-gray-900">Identification Numbers</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="vehicle_identification_number"
                        class="block text-sm font-medium text-gray-700 mb-1">VIN</label>
                    {!! html()->text('vehicle_identification_number')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('vehicle_identification_number'),
                        ])->attributes([
                            'placeholder' => 'Vehicle Identification Number',
                        ]) !!}
                    @error('vehicle_identification_number')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Serial Number
                    </label>
                    {!! html()->text('serial_number')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('serial_number'),
                        ])->attributes([
                            'placeholder' => 'Serial Number',
                        ]) !!}
                    @error('serial_number')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="license_plate" class="block text-sm font-medium text-gray-700 mb-1">
                        License Plate
                    </label>
                    {!! html()->text('license_plate')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('license_plate'),
                        ])->attributes([
                            'placeholder' => 'ABC-1234',
                        ]) !!}
                    @error('license_plate')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="imei" class="block text-sm font-medium text-gray-700 mb-1">IMEI (GPS)</label>
                    <div class="relative">
                        <x-heroicon-o-map-pin
                            class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                        {!! html()->text('imei')->class([
                                'w-full pl-8 pr-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono',
                                'border-red-500' => $errors->has('imei'),
                            ])->attributes([
                                'placeholder' => 'GPS Tracker IMEI',
                            ]) !!}
                        @error('imei')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="sm:col-span-2">

                    <p class="text-xs text-gray-500 @if(isset($equipment) && $equipment?->imei) text-purple-600 @endif">GPS tracker ID for location tracking</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Third Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-3 mb-4">
                <x-heroicon-o-bolt class="h-5 w-5 text-green-600" />
                <h3 class="text-lg font-bold text-gray-900">Power Source</h3>
            </div>

            <div class="grid grid-cols-1  gap-3">
                <div>
                    <label for="power-type-select" class="block text-sm font-medium text-gray-700 mb-1">
                        Power Type
                    </label>
                    {!! html()->select('power_source_type', [
                            '' => 'Select Power Source',
                            'diesel' => 'Diesel',
                            'gas' => 'Gas',
                            'batteries' => 'Batteries',
                            'electric' => 'Electric',
                        ])->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                            'border-red-500' => $errors->has('power_source_type'),
                        ]) !!}
                    @error('power_source_type')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    {!! html()->checkbox('has_def', true)->id('has_def')->checked(old('has_def', isset($equipment) ? ($equipment->has_def === 'Yes' ? true : false) : false))->class('mr-2') !!}
                    <label for="has_def" class="text-sm font-medium text-gray-700">
                        Has DEF (Diesel Exhaust Fluid)
                    </label>
                    @error('has_def')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="diesel_tank_capacity" class="block text-sm font-medium text-gray-700 mb-1">
                        Diesel Tank Capacity (Gallons)
                    </label>
                    {!! html()->text('diesel_tank_capacity')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('diesel_tank_capacity'),
                        ])->attributes([
                            'min' => 0,
                            'data-digit-input' => 'true',
                            'placeholder' => 'Enter capacity in gallons',
                        ]) !!}
                    @error('diesel_tank_capacity')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="def_tank_capacity" class="block text-sm font-medium text-gray-700 mb-1">
                        DEF Tank Capacity (Gallons)
                    </label>
                    {!! html()->text('def_tank_capacity')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('def_tank_capacity'),
                        ])->attributes([
                            'min' => 0,
                            'data-digit-input' => 'true',
                            'placeholder' => 'Enter DEF tank capacity in gallons',
                        ]) !!}
                    @error('def_tank_capacity')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gas_tank_capacity" class="block text-sm font-medium text-gray-700 mb-1">
                        Gas Tank Capacity (Gallons)
                    </label>
                    {!! html()->text('gas_tank_capacity')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('gas_tank_capacity'),
                        ])->attributes([
                            'min' => 0,
                            'data-digit-input' => 'true',
                            'placeholder' => 'Enter gas tank capacity in gallons',
                        ]) !!}
                    @error('gas_tank_capacity')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="standard_battery_count" class="block text-sm font-medium text-gray-700 mb-1">
                        Standard Battery Count
                    </label>
                    {!! html()->text('standard_battery_count')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('standard_battery_count'),
                        ])->attributes([
                            'min' => 0,
                            'data-digit-input' => 'true',
                            'placeholder' => 'Enter number of batteries',
                        ]) !!}
                    @error('standard_battery_count')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="expanded_battery_count" class="block text-sm font-medium text-gray-700 mb-1">
                        Expanded Battery Count
                    </label>
                    {!! html()->text('expanded_battery_count')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('expanded_battery_count'),
                        ])->attributes([
                            'min' => 0,
                            'data-digit-input' => 'true',
                            'placeholder' => 'Enter expanded battery count',
                        ]) !!}
                    @error('expanded_battery_count')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Electric Fields -->
<div id="electric-fields" class="grid grid-cols-1 gap-3 {{ old('power_source_type', $equipment->power_source_type ?? '') === 'electric' ? '' : 'hidden' }}">

    @php
        // Handle old input or DB values (JSON arrays)
        $selectedVolts = old('volts', isset($equipment) ? $equipment->volts ?? [] : []);
        $selectedAmps  = old('amps', isset($equipment) ? $equipment->amps ?? [] : []);
    @endphp

    <!-- Volts -->
    <div>
        <span class="block text-sm font-medium text-gray-700 mb-1">Volts</span>
        <div class="flex gap-4">
            <div>
                <input type="checkbox" name="volts[]" id="volts_110" value="110V"
                    class="mr-2" {{ in_array('110V', $selectedVolts) ? 'checked' : '' }}>
                <label for="volts_110" class="text-sm font-medium text-gray-700">110V</label>
            </div>
            <div>
                <input type="checkbox" name="volts[]" id="volts_220" value="220V"
                    class="mr-2" {{ in_array('220V', $selectedVolts) ? 'checked' : '' }}>
                <label for="volts_220" class="text-sm font-medium text-gray-700">220V</label>
            </div>
        </div>
        @error('volts')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Amps -->
    <div>
        <span class="block text-sm font-medium text-gray-700 mb-1">Amps</span>
        <div class="flex flex-wrap gap-3">
            @foreach([5,10,15,20,25,30,40,50,60,70] as $amp)
                <div>
                    <input type="checkbox" name="amps[]" id="amp_{{ $amp }}" value="{{ $amp }}A"
                        class="mr-2" {{ in_array($amp.'A', $selectedAmps) ? 'checked' : '' }}>
                    <label for="amp_{{ $amp }}" class="text-sm font-medium text-gray-700">{{ $amp }}A</label>
                </div>
            @endforeach
        </div>
        @error('amps')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

</div>






            </div>

        </div>

        {{-- Customer Checklist --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-green-600" />
                <h3 class="text-lg font-bold text-gray-900">Checklist Master</h3>
            </div>

            <div>
                <label for="checklist_master_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Checklist Master
                </label>
                {!! html()->select('checklist_master_id', $checklistMasters)->class([
                        'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                        'border-red-500' => $errors->has('checklist_master_id'),
                    ]) !!}
                <p class="mt-2 text-xs text-gray-500">
                    Customer delivery and return inspection checklist
                </p>
                @error('checklist_master_id')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Equipment Service --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-4">
                <x-heroicon-o-wrench class="h-5 w-5 text-indigo-600" />
                <h3 class="text-lg font-bold text-gray-900">Equipment Service</h3>
            </div>

            <div>
                <label for="equipment_service_id" class="block text-sm font-medium text-gray-700 mb-1">Service
                    Schedule</label>

                <div id="service_display_container">
                    @if(isset($equipment) && $equipment->equipment_service_id && $equipment->serviceTemplate)
                        {{-- Show assigned service template with edit icon --}}
                        <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="h-5 w-5 text-blue-600" />
                                <span class="text-sm font-medium text-gray-900">{{ $equipment->serviceTemplate->name }}</span>
                            </div>
                            <button
                                type="button"
                                onclick="openServiceAssignModal()"
                                class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                title="Edit Service Template"
                            >
                                <x-heroicon-o-pencil class="h-4 w-4" />
                            </button>
                        </div>
                        <input type="hidden" name="equipment_service_id" value="{{ $equipment->equipment_service_id }}" id="equipment_service_id_input" />
                    @else
                        {{-- Show assign button --}}
                        <button
                            type="button"
                            onclick="openServiceAssignModal()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 text-blue-600 border-2 border-dashed border-blue-300 rounded-lg hover:bg-blue-50 hover:border-blue-400 transition-colors"
                        >
                            <x-heroicon-o-plus-circle class="h-5 w-5" />
                            <span class="font-medium">Assign Service Template</span>
                        </button>
                        <input type="hidden" name="equipment_service_id" value="" id="equipment_service_id_input" />
                    @endif
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    Maintenance schedule and service history tracking
                </p>
                @error('equipment_service_id')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Equipment Part List --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-3 mb-4">
                <x-heroicon-o-cube class="h-5 w-5 text-purple-600" />
                <h3 class="text-lg font-bold text-gray-900">Equipment Parts List</h3>
            </div>

            <div>
                <label for="part_id" class="block text-sm font-medium text-gray-700 mb-1">Parts List Template</label>
                {!! html()->select('part_id', ['' => 'Select Parts List Template'])->class([
                        'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                        'border-red-500' => $errors->has('part_id'),
                    ]) !!}
                <p class="mt-2 text-xs text-gray-500">
                    Assign a parts list template for this equipment
                </p>
                @error('part_id')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Equipment Notes - Full Width --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center space-x-2 mb-4">
            <x-heroicon-o-information-circle class="h-5 w-5 text-gray-600" />
            <h3 class="text-lg font-bold text-gray-900">Equipment Notes</h3>
        </div>

        <div>
            <label for="equipment_notes" class="block text-sm font-medium text-gray-700 mb-2">
                Additional Notes and Comments
            </label>
            {!! html()->textarea('equipment_notes')->rows(4)->placeholder(
                    'Enter any additional notes, maintenance history, special instructions, or other relevant information about this equipment...',
                )->class([
                    'tinymce w-full px-4 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none',
                    'border-red-500' => $errors->has('equipment_notes'),
                ]) !!}
        </div>
    </div>
</div>

{{-- Service Template Assignment Modal --}}
<div id="serviceAssignModal" class="hidden fixed inset-0 z-50 flex items-center bg-gray-500/75 transition-opacity justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Assign Service Template</h3>
            <button
                type="button"
                onclick="closeServiceAssignModal()"
                class="text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-5">
            <label for="service_template_select" class="block text-sm font-medium text-gray-700 mb-2">
                Select Service Template
            </label>
            <select
                id="service_template_select"
                class="w-full px-3 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
            >
                <option value="">Select a template...</option>
                @foreach($serviceTemplates as $id => $name)
                    <option value="{{ $id }}" {{ (isset($equipment) && $equipment->equipment_service_id == $id) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <div class="mt-4 flex items-center gap-3">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="bring_service_current"
                        {{ (isset($equipment) && $equipment->bring_service_flag) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                    />
                    <label for="bring_service_current" class="ml-2 text-sm font-medium">
                        Bring Service Current To:
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        id="service_current_hours"
                        value="{{ old('bring_service_hour', isset($equipment) ? $equipment->bring_service_hour : '') }}"
                        placeholder="0"
                        class="w-24 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    />
                    <span class="text-sm font-medium text-gray-700">Hrs</span>
                </div>
            </div>

            {{-- Hidden inputs for form submission --}}
            <input type="hidden" name="bring_service_flag" id="bring_service_flag_input" value="{{ old('bring_service_flag', isset($equipment) ? ($equipment->bring_service_flag ? '1' : '0') : '0') }}" />
            <input type="hidden" name="bring_service_hour" id="bring_service_hour_input" value="{{ old('bring_service_hour', isset($equipment) ? $equipment->bring_service_hour : '') }}" />
        </div>
        <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
            <button
                type="button"
                onclick="closeServiceAssignModal()"
                class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
                Cancel
            </button>
            <button
                type="button"
                onclick="assignServiceTemplate()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                Assign Template
            </button>
        </div>
    </div>
</div>


@push('js')
    <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
    @vite('resources/admin/js/tinymce.js')
    <script>
        // Service Template Modal Functions
        function openServiceAssignModal() {
            document.getElementById('serviceAssignModal').classList.remove('hidden');
        }

        function closeServiceAssignModal() {
            document.getElementById('serviceAssignModal').classList.add('hidden');
        }

        function assignServiceTemplate() {
            const select = document.getElementById('service_template_select');
            const selectedValue = select.value;
            const selectedText = select.options[select.selectedIndex].text;

            if (!selectedValue) {
                alert('Please select a service template');
                return;
            }

            // Update the hidden input
            document.getElementById('equipment_service_id_input').value = selectedValue;

            // Update the UI to show the selected template
            const container = document.getElementById('service_display_container');
            container.innerHTML = `
                <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-900">${selectedText}</span>
                    </div>
                    <button
                        type="button"
                        onclick="openServiceAssignModal()"
                        class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                        title="Edit Service Template"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
                <input type="hidden" name="equipment_service_id" value="${selectedValue}" id="equipment_service_id_input" />
            `;

            closeServiceAssignModal();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Sync bring service current checkbox and hours with hidden inputs
            const bringServiceCheckbox = document.getElementById('bring_service_current');
            const serviceCurrentHours = document.getElementById('service_current_hours');
            const bringServiceFlagInput = document.getElementById('bring_service_flag_input');
            const bringServiceHourInput = document.getElementById('bring_service_hour_input');

            if (bringServiceCheckbox && serviceCurrentHours) {
                // Update hidden inputs when checkbox changes
                bringServiceCheckbox.addEventListener('change', function() {
                    bringServiceFlagInput.value = this.checked ? '1' : '0';
                });

                // Update hidden input when hours change
                serviceCurrentHours.addEventListener('input', function() {
                    bringServiceHourInput.value = this.value;
                });
            }

            const powerTypeSelect = document.querySelector('select[name="power_source_type"]');
            const hasDefCheckbox = document.getElementById('has_def');
            const dieselCapacityField = document.querySelector('input[name="diesel_tank_capacity"]').closest('div');
            const defCapacityField = document.querySelector('input[name="def_tank_capacity"]').closest('div');
            const gasTankCapacityField = document.querySelector('input[name="gas_tank_capacity"]').closest('div');
            const standardBatteryCount = document.querySelector('input[name="standard_battery_count"]').closest(
                'div');
            const expandedBatteryCount = document.querySelector('input[name="expanded_battery_count"]').closest(
                'div');
                const electricFields = document.getElementById('electric-fields');


            function toggleFields() {
                const powerSourceType = powerTypeSelect.value;

                // Hide all fields first
                dieselCapacityField.classList.add('hidden');
                defCapacityField.classList.add('hidden');
                hasDefCheckbox.closest('div').classList.add('hidden');
                gasTankCapacityField.classList.add('hidden');
                standardBatteryCount.classList.add('hidden');
                expandedBatteryCount.classList.add('hidden');
                electricFields.classList.add('hidden');

                // Show only relevant fields based on power source type
                if (powerSourceType === 'diesel') {
                    dieselCapacityField.classList.remove('hidden');
                    hasDefCheckbox.closest('div').classList.remove('hidden');
                    if (hasDefCheckbox.checked) {
                        defCapacityField.classList.remove('hidden');
                    }
                } else if (powerSourceType === 'gas') {
                    gasTankCapacityField.classList.remove('hidden');
                } else if (powerSourceType === 'batteries') {
                    standardBatteryCount.classList.remove('hidden');
                    expandedBatteryCount.classList.remove('hidden');
                } else if (powerSourceType === 'electric') {
                    electricFields.classList.remove('hidden');
                }
            }

            powerTypeSelect.addEventListener('change', toggleFields);
            hasDefCheckbox.addEventListener('change', toggleFields);

            // Initial toggle based on existing values
            toggleFields();

            const isTracked = document.getElementById('is_tracked');
            const overageRate = document.querySelector('input[name="overage_rate"]');

            function toggleOverageRate() {
                if (isTracked.checked) {
                    overageRate.removeAttribute('readonly');
                    overageRate.setAttribute('required', 'required');
                } else {
                    overageRate.setAttribute('readonly', 'readonly');
                    overageRate.removeAttribute('required');
                    overageRate.value = '';
                }
            }
            isTracked.addEventListener('change', toggleOverageRate);
        });
    </script>
@endpush

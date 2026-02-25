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
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('overage_rate')->id('overage_rate')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('overage_rate'),
                                'border-red-500' => $errors->has('overage_rate'),
                            ])->attributes([
                                'data-numeric-input' => 'true',
                                'data-parsley-maxlength' => 8,
                                'maxlength' => 8,
                                'placeholder' => '0.00',
                                'autocomplete' => 'off',
                                old('is_tracked', isset($equipment) ? ($equipment->is_tracked === 'Yes' ? false : true) : false)
                                    ? 'readonly'
                                    : null,
                            ]) !!}
                    </div>
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
                            'w-full px-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors placeholder:italic',
                            'border-gray-300' => !$errors->has('model_year'),
                            'border-red-500' => $errors->has('model_year'),
                        ])->attributes([
                            'min' => 1900,
                            'max' => date('Y') + 1,
                            'placeholder' => 'e.g. 2020',
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
                <input type="checkbox" name="not_for_rent" id="not_for_rent" value="1"
                    class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    @checked(old('not_for_rent', $equipment->not_for_rent ?? false))>
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
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('purchase_cost')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
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
                    <label for="freight_shipping" class="block text-sm font-medium text-gray-700 mb-1">
                        Freight / Shipping
                    </label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('freight_shipping')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('freight_shipping'),
                                'border-red-500' => $errors->has('freight_shipping'),
                            ])->attributes([
                                'data-digit-input' => 'true',
                                'min' => 0,
                                'placeholder' => '0.00',
                                'step' => '0.01',
                            ]) !!}
                    </div>
                    @error('freight_shipping')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="taxes_fees" class="block text-sm font-medium text-gray-700 mb-1">
                        Taxes / Fees
                    </label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('taxes_fees')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('taxes_fees'),
                                'border-red-500' => $errors->has('taxes_fees'),
                            ])->attributes([
                                'data-digit-input' => 'true',
                                'min' => 0,
                                'placeholder' => '0.00',
                                'step' => '0.01',
                            ]) !!}
                    </div>
                    @error('taxes_fees')
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
                    <label for="down_payment" class="block text-sm font-medium text-gray-700 mb-1">
                        Down Payment
                    </label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('down_payment')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('down_payment'),
                                'border-red-500' => $errors->has('down_payment'),
                            ])->attributes([
                                'data-digit-input' => 'true',
                                'min' => 0,
                                'placeholder' => '0.00',
                                'step' => '0.01',
                            ]) !!}
                    </div>
                    @error('down_payment')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="amount_financed" class="block text-sm font-medium text-gray-700 mb-1">
                        Amount Financed
                    </label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('amount_financed')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('amount_financed'),
                                'border-red-500' => $errors->has('amount_financed'),
                            ])->attributes([
                                'data-digit-input' => 'true',
                                'min' => 0,
                                'placeholder' => '0.00',
                                'step' => '0.01',
                            ]) !!}
                    </div>
                    @error('amount_financed')
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
                    {!! html()->text('interest_rate', isset($equipment) && $equipment->interest_rate ? $equipment->interest_rate . '%' : old('interest_rate'))->id('interest_rate')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('interest_rate'),
                        ])->attributes([
                            'placeholder' => '1.99%',
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
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">
                            $
                        </span>
                        {!! html()->text('monthly_payment')->class([
                                'w-full pl-5 pr-3 py-3 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
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

                <div>
                    <label for="warranty_duration_months" class="block text-sm font-medium text-gray-700 mb-1">
                        Warranty Duration (Months)
                    </label>
                    {!! html()->number('warranty_duration_months')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('warranty_duration_months'),
                        ])->attributes([
                            'min' => 0,
                            'placeholder' => 'Warranty Duration (Months)',
                        ]) !!}
                    @error('warranty_duration_months')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warranty_duration_hours" class="block text-sm font-medium text-gray-700 mb-1">
                        Warranty Duration (Hours)
                    </label>
                    {!! html()->number('warranty_duration_hours')->class([
                            'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                            'border-red-500' => $errors->has('warranty_duration_hours'),
                        ])->attributes([
                            'min' => 0,
                            'placeholder' => 'Warranty Duration (Hours)',
                        ]) !!}
                    @error('warranty_duration_hours')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">

                    <p class="text-xs text-gray-500 @if (isset($equipment) && $equipment?->imei) text-purple-600 @endif">GPS
                        tracker ID for location tracking</p>
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

            <div class="grid grid-cols-1 gap-3">
                @php
                    // Handle old input or DB values (JSON arrays)
                    $selectedVolts = old('volts', isset($equipment) ? $equipment->volts ?? [] : []);
                    $selectedAmps = old('amps', isset($equipment) ? $equipment->amps ?? [] : []);
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-start">
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

                    <div id="diesel-capacity-field"
                        class="{{ old('power_source_type', $equipment->power_source_type ?? '') === 'diesel' ? '' : 'hidden' }}">
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

                    <div id="gas-capacity-field"
                        class="{{ old('power_source_type', $equipment->power_source_type ?? '') === 'gas' ? '' : 'hidden' }}">
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

                    <div id="electric-volts-field"
                        class="{{ old('power_source_type', $equipment->power_source_type ?? '') === 'electric' ? '' : 'hidden' }}">
                        <span class="block text-sm font-medium text-gray-700 mb-1">Volts</span>
                        <div class="flex gap-4">
                            <div>
                                <input type="checkbox" name="volts[]" id="volts_110" value="110V" class="mr-2"
                                    {{ in_array('110V', $selectedVolts) ? 'checked' : '' }}>
                                <label for="volts_110" class="text-sm font-medium text-gray-700">110V</label>
                            </div>
                            <div>
                                <input type="checkbox" name="volts[]" id="volts_220" value="220V" class="mr-2"
                                    {{ in_array('220V', $selectedVolts) ? 'checked' : '' }}>
                                <label for="volts_220" class="text-sm font-medium text-gray-700">220V</label>
                            </div>
                        </div>
                        @error('volts')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="diesel-def-row"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center {{ old('power_source_type', $equipment->power_source_type ?? '') === 'diesel' ? '' : 'hidden' }}">
                    <div id="has-def-field" class="flex items-center gap-2">
                        {!! html()->checkbox('has_def', true)->id('has_def')->checked(old('has_def', isset($equipment) ? ($equipment->has_def === 'Yes' ? true : false) : false))->class('mr-2') !!}
                        <label for="has_def" class="text-sm font-medium text-gray-700">
                            Has DEF (Diesel Exhaust Fluid)
                        </label>
                        @error('has_def')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="def-capacity-field"
                        class="{{ old('has_def', isset($equipment) ? ($equipment->has_def === 'Yes') : false) ? '' : 'hidden' }}">
                        <label for="def_tank_capacity" class="block text-sm font-medium text-gray-700 mb-1">
                            DEF Tank Capacity (Gallons)
                        </label>
                        {!! html()->text('def_tank_capacity')->class([
                                'w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
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
                </div>

                <div id="battery-fields"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 {{ old('power_source_type', $equipment->power_source_type ?? '') === 'batteries' ? '' : 'hidden' }}">
                    <div>
                        <label for="standard_battery_count" class="block text-sm font-medium text-gray-700 mb-1">
                            Standard Battery Count
                        </label>
                        {!! html()->text('standard_battery_count')->class([
                                'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-red-500' => $errors->has('standard_battery_count'),
                            ])->attributes([
                                'min' => 0,
                                'data-numeric-input' => 'true',
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
                                'px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-red-500' => $errors->has('expanded_battery_count'),
                            ])->attributes([
                                'min' => 0,
                                'data-numeric-input' => 'true',
                                'placeholder' => 'Enter expanded battery count',
                            ]) !!}
                        @error('expanded_battery_count')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Electric Fields -->
                <div id="electric-fields"
                    class="grid grid-cols-1 gap-3 {{ old('power_source_type', $equipment->power_source_type ?? '') === 'electric' ? '' : 'hidden' }}">
                    <!-- Amps -->
                    <div>
                        <span class="block text-sm font-medium text-gray-700 mb-1">Amps</span>
                        <div class="flex flex-wrap gap-3">
                            @foreach ([5, 10, 15, 20, 25, 30, 40, 50, 60, 70] as $amp)
                                <div>
                                    <input type="checkbox" name="amps[]" id="amp_{{ $amp }}"
                                        value="{{ $amp }}A" class="mr-2"
                                        {{ in_array($amp . 'A', $selectedAmps) ? 'checked' : '' }}>
                                    <label for="amp_{{ $amp }}"
                                        class="text-sm font-medium text-gray-700">{{ $amp }}A</label>
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
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-clipboard-document-list class="h-5 w-5 text-green-600" />
                    <h3 class="text-lg font-bold text-gray-900">Checklist Master</h3>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="checklist_master_id" class="block text-sm font-medium text-gray-700">
                        Checklist Master
                    </label>
                    @if (isset($equipment) && $equipment->checklist_master_id)
                        <a href="{{ route('admin.checklist-management.checklist-master.edit', $equipment->checklistMaster->unique_id) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center text-gray-500 hover:text-blue-600"
                            title="View Checklist Master">
                            <x-heroicon-o-eye class="h-4 w-4" />
                        </a>
                    @endif
                </div>
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
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-wrench class="h-5 w-5 text-indigo-600" />
                    <h3 class="text-lg font-bold text-gray-900">Equipment Service</h3>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="equipment_service_id" class="block text-sm font-medium text-gray-700">Service
                        Schedule</label>
                    @if (isset($equipment) && $equipment->equipment_service_id && $equipment->serviceTemplate)
                        <a href="{{ route('admin.maintenance-management.service-master.index', ['tab' => 'templates']) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center text-gray-500 hover:text-blue-600"
                            title="View Service Template">
                            <x-heroicon-o-eye class="h-4 w-4" />
                        </a>
                    @endif
                </div>

                <div id="service_display_container">
                    @if (isset($equipment) && $equipment->equipment_service_id && $equipment->serviceTemplate)
                        {{-- Show assigned service template with edit icon --}}
                        <div
                            class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="h-5 w-5 text-blue-600" />
                                <span
                                    class="text-sm font-medium text-gray-900">{{ $equipment->serviceTemplate->name }}</span>
                            </div>
                            <button type="button" onclick="openServiceAssignModal()"
                                class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                title="Edit Service Template">
                                <x-heroicon-o-pencil class="h-4 w-4" />
                            </button>
                        </div>
                        <input type="hidden" name="equipment_service_id"
                            value="{{ $equipment->equipment_service_id }}" id="equipment_service_id_input" />
                    @else
                        {{-- Show assign button --}}
                        <button type="button" onclick="openServiceAssignModal()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 text-blue-600 border-2 border-dashed border-blue-300 rounded-lg hover:bg-blue-50 hover:border-blue-400 transition-colors">
                            <x-heroicon-o-plus-circle class="h-5 w-5" />
                            <span class="font-medium">Assign Service Template</span>
                        </button>
                        <input type="hidden" name="equipment_service_id" value=""
                            id="equipment_service_id_input" />
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


        <div class="bg-white rounded-xl shadow-sm border p-5">

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <x-heroicon-o-cube class="h-5 w-5 text-purple-600" />
                    <h3 class="text-lg font-bold text-gray-900">Equipment Parts List</h3>
                </div>
            </div>

            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">
                    Parts List Templates
                </label>
                @if (isset($equipment) && $equipment->parts_list_id)
                    <a href="{{ route('admin.maintenance-management.parts.parts-list.view', $equipment->partsList->unique_id) }}"
                        target="_blank" rel="noopener"
                        class="inline-flex items-center text-gray-500 hover:text-blue-600" title="View Parts List">
                        <x-heroicon-o-eye class="h-4 w-4" />
                    </a>
                @endif
            </div>

            <!-- <select
                name="parts_lists[]"
                id="parts_lists_Select"
                multiple
                class="choices-select w-full rounded-md border border-gray-300 text-sm">
            </select> -->

            {!! html()->select('parts_list_id', ['' => 'Select Parts List'], $selectedPartsListId ?? null)->id('parts_lists_Select')->class([
                    'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white',
                ]) !!}



            <p class="mt-2 text-xs text-gray-500">
                Select parts lists applicable to this equipment
            </p>
        </div>


    </div>

    {{-- Document Images - Full Width --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-document-text class="h-5 w-5 text-red-600" />
                <h3 class="text-lg font-bold text-gray-900">Document Images</h3>
                <span class="text-xs text-gray-500">Max 2 MB per file</span>
            </div>
            <div class="flex items-center gap-3">
                <input type="file" name="document_images[]" id="document_images" class="hidden"
                    accept="image/png,image/jpeg,application/pdf" multiple />
                <label for="document_images"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold cursor-pointer hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Upload Files
                </label>
            </div>
        </div>
        <div id="document_images_remove_inputs"></div>

        @error('document_images')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('document_images.*')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div id="document_images_preview" class="mt-4 hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-700">Selected images</span>
                <span id="document_images_preview_count" class="text-xs text-gray-500"></span>
            </div>
            <div id="document_images_preview_grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4"></div>
        </div>

        @if (isset($equipment) && $equipment->documentImages->isNotEmpty())
            <div id="document_images_existing" class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($equipment->documentImages as $document)
                    @php($media = $document->media)
                    <div class="relative border border-gray-200 rounded-lg p-2"
                        data-document-id="{{ $document->id }}">
                        <button type="button"
                            class="document-image-remove absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow hover:text-red-600"
                            title="Remove">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                        <div
                            class="aspect-square bg-gray-50 rounded-md overflow-hidden flex items-center justify-center">
                            @if ($media)
                                @if (\Illuminate\Support\Str::contains((string) $media->mime_type, 'pdf'))
                                    <div class="h-full w-full flex flex-col items-center justify-center text-gray-500">
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                            <path d="M14 2v6h6" />
                                            <path d="M8 13h8" />
                                            <path d="M8 17h5" />
                                        </svg>
                                        <span class="mt-1 text-[10px] font-semibold">PDF</span>
                                    </div>
                                @else
                                    <img src="{{ $media->getUrl() }}"
                                        alt="{{ $media->original_file_name ?? 'Document image' }}"
                                        class="h-full w-full object-cover" />
                                @endif
                            @else
                                <span class="text-xs text-gray-400">No file</span>
                            @endif
                        </div>
                        <div class="mt-2 text-xs text-gray-600 truncate">
                            {{ $media->original_file_name ?? 'Document image' }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            @if ($media)
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 border border-blue-200 rounded hover:bg-blue-50">
                                    View
                                </a>
                                <a href="{{ $media->downloadMedia() }}"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 border border-gray-200 rounded hover:bg-gray-50">
                                    Download
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div id="document_images_new_container" class="contents"></div>
            </div>
        @else
            <p class="mt-3 text-xs text-gray-500">No document images uploaded.</p>
        @endif
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
<div id="serviceAssignModal"
    class="hidden fixed inset-0 z-50 flex items-center bg-gray-500/75 transition-opacity justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Assign Service Template</h3>
            <button type="button" onclick="closeServiceAssignModal()"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-5">
            <label for="service_template_select" class="block text-sm font-medium text-gray-700 mb-2">
                Select Service Template
            </label>
            <select id="service_template_select"
                class="w-full px-3 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                <option value="">Select a template...</option>
                @foreach ($serviceTemplates as $id => $name)
                    <option value="{{ $id }}"
                        {{ isset($equipment) && $equipment->equipment_service_id == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <div class="mt-4 flex items-center gap-3">
                <div class="flex items-center">
                    <input type="checkbox" id="bring_service_current"
                        {{ isset($equipment) && $equipment->bring_service_flag ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500" />
                    <label for="bring_service_current" class="ml-2 text-sm font-medium">
                        Bring Service Current To:
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" id="service_current_hours"
                        value="{{ old('bring_service_hour', isset($equipment) ? $equipment->bring_service_hour : '') }}"
                        placeholder="0"
                        class="w-24 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" />
                    <span class="text-sm font-medium text-gray-700">Hrs</span>
                </div>
            </div>

            {{-- Hidden inputs for form submission --}}
            <input type="hidden" name="bring_service_flag" id="bring_service_flag_input"
                value="{{ old('bring_service_flag', isset($equipment) ? ($equipment->bring_service_flag ? '1' : '0') : '0') }}" />
            <input type="hidden" name="bring_service_hour" id="bring_service_hour_input"
                value="{{ old('bring_service_hour', isset($equipment) ? $equipment->bring_service_hour : '') }}" />
        </div>
        <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
            <button type="button" onclick="closeServiceAssignModal()"
                class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                Cancel
            </button>
            <button type="button" onclick="assignServiceTemplate()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
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
            // Interest Rate % formatting
            const interestRateInput = document.getElementById('interest_rate');
            if (interestRateInput) {
                // Format on input
                interestRateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/%/g, '').trim();
                    if (value) {
                        e.target.value = value + '%';
                    }
                });

                // Format on blur
                interestRateInput.addEventListener('blur', function(e) {
                    let value = e.target.value.replace(/%/g, '').trim();
                    if (value) {
                        e.target.value = value + '%';
                    }
                });

                // Remove % before form submission
                const form = document.getElementById('productForm');
                if (form) {
                    form.addEventListener('submit', function() {
                        let value = interestRateInput.value.replace(/%/g, '').trim();
                        interestRateInput.value = value;
                    });
                }
            }

            // Date Acquired auto-formatting with slashes according to data-format
            const dateAcquiredInput = document.querySelector('input[name="date_acquired"]');
            if (dateAcquiredInput) {
                const dateFormat = dateAcquiredInput.getAttribute('data-format') || 'mm/dd/yyyy';

                dateAcquiredInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove all non-digits
                    let formattedValue = '';

                    if (value.length > 0) {
                        const format = dateFormat.toLowerCase();

                        if (format.includes('mm') && format.includes('dd') && format.includes('yyyy')) {
                            // MM/DD/YYYY or DD/MM/YYYY or YYYY/MM/DD format
                            const firstPos = format.indexOf('m');
                            const secondPos = format.indexOf('d');
                            const thirdPos = format.indexOf('y');

                            const positions = [
                                { pos: firstPos, type: 'm', len: 2 },
                                { pos: secondPos, type: 'd', len: 2 },
                                { pos: thirdPos, type: 'y', len: 4 }
                            ].sort((a, b) => a.pos - b.pos);

                            let digitIndex = 0;
                            let dateObj = { m: '', d: '', y: '' };

                            for (let part of positions) {
                                const partLen = part.type === 'y' ? 4 : 2;
                                dateObj[part.type] = value.substring(digitIndex, digitIndex + partLen);
                                digitIndex += partLen;

                                if (digitIndex >= value.length) break;
                            }

                            // Build formatted value based on format
                            const formatLower = format.replace(/\//g, '-');
                            let result = formatLower;
                            result = result.replace('mm', dateObj.m).replace('dd', dateObj.d).replace('yyyy', dateObj.y);
                            result = result.replace(/-/g, '/');
                            formattedValue = result;
                        }
                    }

                    e.target.value = formattedValue;
                });
            }

            const documentImagesInput = document.getElementById('document_images');
            const previewContainer = document.getElementById('document_images_preview');
            const previewGrid = document.getElementById('document_images_preview_grid');
            const previewCount = document.getElementById('document_images_preview_count');

            if (documentImagesInput && previewContainer && previewGrid && previewCount) {
                const existingGrid = document.getElementById('document_images_existing');
                const newContainer = document.getElementById('document_images_new_container');

                const renderDocumentPreviews = (files) => {
                    const targetGrid = newContainer || previewGrid;
                    targetGrid.innerHTML = '';

                    if (!files.length) {
                        previewContainer.classList.add('hidden');
                        previewCount.textContent = '';
                        return;
                    }

                    previewContainer.classList.remove('hidden');
                    previewCount.textContent = `${files.length} selected`;

                    if (existingGrid && newContainer) {
                        previewGrid.classList.add('hidden');
                    }

                    files.forEach((file, index) => {
                        const isPdf = file.type === 'application/pdf' || file.name.toLowerCase()
                            .endsWith('.pdf');
                        const objectUrl = isPdf ? null : URL.createObjectURL(file);
                        const card = document.createElement('div');
                        card.className = 'relative border border-gray-200 rounded-lg p-2';

                        card.innerHTML = `
                            <button
                                type="button"
                                data-index="${index}"
                                class="absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow hover:text-red-600"
                                title="Remove"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                            <div class="aspect-square bg-gray-50 rounded-md overflow-hidden flex items-center justify-center">
                                ${isPdf ? `
                                        <div class="h-full w-full flex flex-col items-center justify-center text-gray-500">
                                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                                <path d="M14 2v6h6" />
                                                <path d="M8 13h8" />
                                                <path d="M8 17h5" />
                                            </svg>
                                            <span class="mt-1 text-[10px] font-semibold">PDF</span>
                                        </div>
                                    ` : `
                                        <img src="${objectUrl}" alt="${file.name}" class="h-full w-full object-cover" />
                                    `}
                            </div>
                            <div class="mt-2 text-xs text-gray-600 truncate">${file.name}</div>
                        `;

                        if (!isPdf && objectUrl) {
                            const img = card.querySelector('img');
                            if (img) {
                                img.addEventListener('load', function() {
                                    URL.revokeObjectURL(objectUrl);
                                });
                            }
                        }

                        const removeButton = card.querySelector('button[data-index]');
                        removeButton.addEventListener('click', function() {
                            const removeIndex = Number(this.getAttribute('data-index'));
                            const updatedFiles = files.filter((_, fileIndex) => fileIndex !==
                                removeIndex);
                            const dataTransfer = new DataTransfer();
                            updatedFiles.forEach((updatedFile) => dataTransfer.items.add(
                                updatedFile));
                            documentImagesInput.files = dataTransfer.files;
                            renderDocumentPreviews(updatedFiles);
                        });

                        targetGrid.appendChild(card);
                    });
                };

                documentImagesInput.addEventListener('change', function() {
                    const files = Array.from(this.files || []);
                    renderDocumentPreviews(files);
                });
            }

            const existingImages = document.getElementById('document_images_existing');
            const removeInputs = document.getElementById('document_images_remove_inputs');

            if (existingImages && removeInputs) {
                existingImages.addEventListener('click', function(event) {
                    const button = event.target.closest('.document-image-remove');
                    if (!button) return;

                    const card = button.closest('[data-document-id]');
                    if (!card) return;

                    const documentId = card.getAttribute('data-document-id');
                    if (!documentId) return;

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'document_images_remove[]';
                    input.value = documentId;
                    removeInputs.appendChild(input);

                    card.remove();
                });
            }

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
            const dieselCapacityField = document.getElementById('diesel-capacity-field');
            const dieselDefRow = document.getElementById('diesel-def-row');
            const defCapacityField = document.getElementById('def-capacity-field');
            const gasTankCapacityField = document.getElementById('gas-capacity-field');
            const batteryFields = document.getElementById('battery-fields');
            const electricVoltsField = document.getElementById('electric-volts-field');
            const electricFields = document.getElementById('electric-fields');


            function toggleFields() {
                const powerSourceType = powerTypeSelect.value;

                // Hide all fields first
                dieselCapacityField.classList.add('hidden');
                dieselDefRow.classList.add('hidden');
                defCapacityField.classList.add('hidden');
                gasTankCapacityField.classList.add('hidden');
                batteryFields.classList.add('hidden');
                electricVoltsField.classList.add('hidden');
                electricFields.classList.add('hidden');

                // Show only relevant fields based on power source type
                if (powerSourceType === 'diesel') {
                    dieselCapacityField.classList.remove('hidden');
                    dieselDefRow.classList.remove('hidden');
                    if (hasDefCheckbox.checked) {
                        defCapacityField.classList.remove('hidden');
                    }
                } else if (powerSourceType === 'gas') {
                    gasTankCapacityField.classList.remove('hidden');
                } else if (powerSourceType === 'batteries') {
                    batteryFields.classList.remove('hidden');
                } else if (powerSourceType === 'electric') {
                    electricVoltsField.classList.remove('hidden');
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


    <!-- <script>
        let partsListChoices;

        document.addEventListener('DOMContentLoaded', function() {
            partsListChoices = new Choices('#parts_lists_Select', {
                removeItemButton: true,
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Select Parts Lists'
            });

            // Disable initially
            partsListChoices.disable();
        });
    </script> -->



    <script>
        // function loadPartsListsByCategory(categoryId, preselectedIds = []) {
        //     // Hard reset
        //     partsListChoices.hideDropdown();
        //     partsListChoices.removeActiveItems();
        //     partsListChoices.clearChoices();
        //     partsListChoices.disable();

        //     if (!categoryId) return;

        //     const url = window.routes.partsListsByCategory.replace(':id', categoryId);

        //     fetch(url)
        //         .then(res => res.json())
        //         .then(res => {
        //             if (!res.success) return;

        //             const choices = res.data.map(item => ({
        //                 value: String(item.id),
        //                 label: item.name,
        //                 selected: preselectedIds.includes(String(item.id))
        //             }));

        //             partsListChoices.setChoices(choices, 'value', 'label', true);
        //             partsListChoices.enable();
        //         })
        //         .catch(err => {
        //             console.error('Failed to load parts lists', err);
        //             partsListChoices.disable();
        //         });
        // }
    </script>

    <script>
        window.routes = {
            partsListsByCategory: "{{ route('admin.maintenance-management.equipment.get-parts-lists', ':id') }}"
        };

        window.selectedPartsListId = @json($selectedPartsListId ?? null);

        //  window.selectedPartsListIds = @json($selectedPartsListIds ?? []);
    </script>
    <script>
        function loadPartsListsByCategory(categoryId, selectedId = null) {
            const select = document.getElementById('parts_lists_Select');
            if (!select) return;

            select.innerHTML = '<option value="">Select Parts List</option>';

            if (!categoryId) return;

            const url = window.routes.partsListsByCategory.replace(':id', categoryId);

            fetch(url)
                .then(res => res.json())
                .then(res => {
                    if (!res.success) return;

                    res.data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;

                        if (String(item.id) === String(selectedId)) {
                            opt.selected = true;
                        }

                        select.appendChild(opt);
                    });

                    // Let Choices update UI
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });
        }
    </script>


    <script>
        document.addEventListener('change', function(e) {
            if (e.target.id !== 'product_category_id') return;

            loadPartsListsByCategory(e.target.value, null);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('product_category_id');
            if (!categorySelect || !categorySelect.value) return;

            loadPartsListsByCategory(
                categorySelect.value,
                window.selectedPartsListId
            );
        });
    </script>
@endpush

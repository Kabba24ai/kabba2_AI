{{-- Price Settings (Tailwind + Vanilla JS) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-lock-closed class="h-5 w-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Price Settings</h3>
    </div>

    <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0 mb-6">
        <div>
            <label for="diesel_price_per_gallon" class="block text-sm font-medium text-gray-700 mb-1 required">
                Diesel Price Per Gallon
            </label>

            <div class="relative">
                {!! html()->input(
                        'text',
                        'diesel_price_per_gallon',
                        $settings['Price Settings']['diesel_price_per_gallon']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('diesel_price_per_gallon'),
                        'border-red-500' => $errors->has('diesel_price_per_gallon'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Price Settings']['diesel_price_per_gallon']['placeholder'],
                        'required' => true,
                        'id' => 'diesel_price_per_gallon',
                    ]) !!}
            </div>

            @error('diesel_price_per_gallon')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="gas_price_per_gallon" class="block text-sm font-medium text-gray-700 mb-1 required">
                Gas Price Per Gallon
            </label>

            <div class="relative">
                {!! html()->input(
                        'text',
                        'gas_price_per_gallon',
                        $settings['Price Settings']['gas_price_per_gallon']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('gas_price_per_gallon'),
                        'border-red-500' => $errors->has('gas_price_per_gallon'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Price Settings']['gas_price_per_gallon']['placeholder'],
                        'required' => true,
                        'id' => 'gas_price_per_gallon',
                    ]) !!}
            </div>

            @error('gas_price_per_gallon')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="def_price_per_gallon" class="block text-sm font-medium text-gray-700 mb-1 required">
                DEF Price Per Gallon
            </label>

            <div class="relative">
                {!! html()->input(
                        'text',
                        'def_price_per_gallon',
                        $settings['Price Settings']['def_price_per_gallon']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('def_price_per_gallon'),
                        'border-red-500' => $errors->has('def_price_per_gallon'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Price Settings']['def_price_per_gallon']['placeholder'],
                        'required' => true,
                        'id' => 'def_price_per_gallon',
                    ]) !!}
            </div>

            @error('def_price_per_gallon')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>


    </div>
</div>

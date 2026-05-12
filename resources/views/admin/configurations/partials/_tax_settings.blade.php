<div class="col-span-1">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 h-full">
        <div class="flex items-center space-x-2 mb-4">
            <x-heroicon-o-currency-dollar class="h-5 w-5 text-blue-600" />
            <h3 class="text-lg font-bold text-gray-900">Tax Settings</h3>
        </div>

        <div class="space-y-4">
            <div>
                <label for="sales_tax" class="block text-sm font-medium text-gray-700 mb-1">
                    Sales Tax Rate (%)
                </label>

                <div class="relative">
                    {!! html()->input(
                            'text',
                            'sales_tax',
                            \App\Helpers\CustomHelper::displayPercentage($settings['Product Settings']['sales_tax']['setting_value']),
                        )->class([
                            'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                            'border-gray-300' => !$errors->has('sales_tax'),
                            'border-red-500' => $errors->has('sales_tax'),
                        ])->attributes([
                            'autocomplete' => 'off',
                            'placeholder' => $settings['Product Settings']['sales_tax']['placeholder'],
                            'required' => true,
                            'id' => 'sales_tax',
                        ]) !!}
                </div>

                @error('sales_tax')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                <div class="sm:col-span-3">
                    <label for="special_taxes" class="block text-sm font-medium text-gray-700 mb-1">
                        Special Taxes (%)
                    </label>

                    <div class="relative">
                        {!! html()->input(
                                'text',
                                'special_taxes',
                                \App\Helpers\CustomHelper::displayPercentage(data_get($settings, 'Product Settings.special_taxes.setting_value', 0)),
                            )->class([
                                'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                                'border-gray-300' => !$errors->has('special_taxes'),
                                'border-red-500' => $errors->has('special_taxes'),
                            ])->attributes([
                                'autocomplete' => 'off',
                                'placeholder' => data_get($settings, 'Product Settings.special_taxes.placeholder', '0.00'),
                                'id' => 'special_taxes',
                            ]) !!}
                    </div>

                    @error('special_taxes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-9">
                    <label for="special_taxes_description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>

                    <div class="relative">
                        {!! html()->text(
                                'special_taxes_description',
                                old('special_taxes_description', data_get($settings, 'Product Settings.special_taxes_description.setting_value')),
                            )->class([
                                'w-50 rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('special_taxes_description'),
                                'border-red-500' => $errors->has('special_taxes_description'),
                            ])->attributes([
                                'autocomplete' => 'off',
                                'maxlength' => 255,
                                'placeholder' => data_get($settings, 'Product Settings.special_taxes_description.placeholder', ''),
                                'id' => 'special_taxes_description',
                            ]) !!}
                    </div>

                    @error('special_taxes_description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                <div class="sm:col-span-3">
                    <label for="added_fees" class="block text-sm font-medium text-gray-700 mb-1">
                        Added Fees ($)
                    </label>

                    <div class="relative">
                        {!! html()->input(
                                'number',
                                'added_fees',
                                old('added_fees', data_get($settings, 'Product Settings.added_fees.setting_value', 0)),
                            )->class([
                                'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                                'border-gray-300' => !$errors->has('added_fees'),
                                'border-red-500' => $errors->has('added_fees'),
                            ])->attributes([
                                'autocomplete' => 'off',
                                'step' => '0.01',
                                'min' => '0',
                                'placeholder' => data_get($settings, 'Product Settings.added_fees.placeholder', '0.00'),
                                'id' => 'added_fees',
                            ]) !!}
                    </div>

                    @error('added_fees')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-9">
                    <label for="added_fees_description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>

                    <div class="relative">
                        {!! html()->text(
                                'added_fees_description',
                                old('added_fees_description', data_get($settings, 'Product Settings.added_fees_description.setting_value')),
                            )->class([
                                'w-50 rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                'border-gray-300' => !$errors->has('added_fees_description'),
                                'border-red-500' => $errors->has('added_fees_description'),
                            ])->attributes([
                                'autocomplete' => 'off',
                                'maxlength' => 255,
                                'placeholder' => data_get($settings, 'Product Settings.added_fees_description.placeholder', ''),
                                'id' => 'added_fees_description',
                            ]) !!}
                    </div>

                    @error('added_fees_description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
<div class="hidden lg:block lg:col-span-1"></div>

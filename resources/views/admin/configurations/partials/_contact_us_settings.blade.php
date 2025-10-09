{{-- Contact Us Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-phone class="h-5 w-5 text-green-600" />
        <h3 class="text-lg font-bold text-gray-900">Contact Us Settings</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="sales_phone" class="block text-sm font-medium text-gray-700 mb-1 required">
                Phone - Sales
            </label>

            <div class="relative">
                {!! html()->input('text', 'sales_phone', $settings['Contact Us Settings']['sales_phone']['setting_value'])->class([
                        'masked-phone w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('sales_phone'),
                        'border-red-500' => $errors->has('sales_phone'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['sales_phone']['placeholder'],
                        'required' => true,
                        'id' => 'sales_phone',
                    ]) !!}
            </div>

            @error('sales_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="support_phone" class="block text-sm font-medium text-gray-700 mb-1">
                Phone - Support
            </label>

            <div class="relative">
                {!! html()->input('text', 'support_phone', $settings['Contact Us Settings']['support_phone']['setting_value'])->class([
                        'masked-phone w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('support_phone'),
                        'border-red-500' => $errors->has('support_phone'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['support_phone']['placeholder'],
                        'id' => 'support_phone',
                    ]) !!}
            </div>

            @error('support_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="sales_email" class="block text-sm font-medium text-gray-700 mb-1">
                Email - Sales
            </label>

            <div class="relative">
                {!! html()->input('email', 'sales_email', $settings['Contact Us Settings']['sales_email']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('sales_email'),
                        'border-red-500' => $errors->has('sales_email'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['sales_email']['placeholder'],
                        'id' => 'sales_email',
                    ]) !!}
            </div>

            @error('sales_email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="support_email" class="block text-sm font-medium text-gray-700 mb-1">
                Email - Support
            </label>

            <div class="relative">
                {!! html()->input('email', 'support_email', $settings['Contact Us Settings']['support_email']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('support_email'),
                        'border-red-500' => $errors->has('support_email'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['support_email']['placeholder'],
                        'id' => 'support_email',
                    ]) !!}
            </div>

            @error('support_email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="address1" class="block text-sm font-medium text-gray-700 mb-1">
                Address 1
            </label>

            <div class="relative">
                {!! html()->input('text', 'address1', $settings['Contact Us Settings']['address1']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('address1'),
                        'border-red-500' => $errors->has('address1'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['address1']['placeholder'],
                        'id' => 'address1',
                        'required' => true,
                    ]) !!}
            </div>

            @error('address1')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="address2" class="block text-sm font-medium text-gray-700 mb-1 required">
                Address 2
            </label>

            <div class="relative">
                {!! html()->input('text', 'address2', $settings['Contact Us Settings']['address2']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('address2'),
                        'border-red-500' => $errors->has('address2'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['address2']['placeholder'],
                        'id' => 'address2',
                    ]) !!}
            </div>

            @error('address2')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Notice --}}
        <div class="mt-4 rounded-md p-4 md:col-span-2 text-left bg-amber-50 border border-amber-200 text-amber-900">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 mt-0.5" />
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Notice</h4>
                    <p class="text-sm mt-1 text-amber-700">
                        Note: Store locations/addresses that appear on the Contact Us page are managed in Store Settings. Go to Store Settings page
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Contact Us Settings --}}

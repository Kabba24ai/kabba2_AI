<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-document-text class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Invoice And Receipt</h3>
    </div>

    <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0 mb-6">
       
                <!-- Email -->
        <div>
            <label for="invoice_email"
                   class="block text-sm font-medium text-gray-700 mb-1">
                Invoice Email
            </label>

            {!! html()->email(
                'invoice_email',
                old('invoice_email', $settings['Invoice Settings']['invoice_email']['setting_value'] ?? '')
            )->class([
                'w-full rounded-md border px-3 py-3 text-sm shadow-sm focus:ring-2 focus:ring-blue-500',
                'border-red-500' => $errors->has('invoice_email'),
                'border-gray-300' => !$errors->has('invoice_email'),
            ])->attributes([
                'id' => 'invoice_email',
                'placeholder' => 'Enter invoice email',
                'autocomplete' => 'off',
                'data-parsley-type' => 'email',
                'data-parsley-trigger' => 'change',
            ]) !!}

            @error('invoice_email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone -->
        <div>
            <label for="invoice_phone"
                   class="block text-sm font-medium text-gray-700 mb-1">
                Invoice Phone Number
            </label>

            {!! html()->text(
                'invoice_phone',
                old('invoice_phone', $settings['Invoice Settings']['invoice_phone']['setting_value'] ?? '')
            )->class([
                'masked-phone w-full rounded-md border px-3 py-3 text-sm shadow-sm focus:ring-2 focus:ring-blue-500',
                'border-red-500' => $errors->has('invoice_phone'),
                'border-gray-300' => !$errors->has('invoice_phone'),
            ])->attributes([
                'id' => 'invoice_phone',
                'maxlength' => 14,
                'placeholder' => '(xxx) xxx-xxxx',
                'autocomplete' => 'tel',
                'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
            ]) !!}

            @error('invoice_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

       
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-document-text class="h-5 w-5 text-blue-600" />
        <h3 class="text-lg font-bold text-gray-900">Invoice Receipt</h3>
    </div>

    <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-6 sm:space-y-0 mb-6">
        <div>
            <label for="standard_delivery_range" class="block text-sm font-medium text-gray-700 mb-1">
                Due Date Pay Upon Receipt
            </label>

            <div class="relative">
                {!! html()->input(
                        'number',
                        'due_date_pay_upon_receipt',
                        $settings['Invoice Settings']['due_date_pay_upon_receipt']['setting_value'],
                    )->class([
                        'w-20 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('due_date_pay_upon_receipt'),
                        'border-red-500' => $errors->has('due_date_pay_upon_receipt'),
                    ])->attributes([
                        'data-parsley-type' => 'number',
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Invoice Settings']['due_date_pay_upon_receipt']['placeholder'],
                        'required' => true,
                        'id' => 'due_date_pay_upon_receipt',
                    ]) !!}
            </div>

            @error('due_date_pay_upon_receipt')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

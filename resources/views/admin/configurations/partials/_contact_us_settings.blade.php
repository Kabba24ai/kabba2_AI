{{-- Contact Us Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-phone class="h-5 w-5 text-green-600" />
        <h3 class="text-lg font-bold text-gray-900">Contact Us Settings</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                Mobile
            </label>

            <div class="relative">
                {!! html()->input('text', 'mobile', $settings['Contact Us Settings']['mobile']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('mobile'),
                        'border-red-500' => $errors->has('mobile'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['mobile']['placeholder'],
                        'required' => false,
                        'id' => 'mobile',
                    ]) !!}
            </div>

            @error('mobile')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email
            </label>

            <div class="relative">
                {!! html()->input('email', 'email', $settings['Contact Us Settings']['email']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('email'),
                        'border-red-500' => $errors->has('email'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['email']['placeholder'],
                        'required' => false,
                        'id' => 'email',
                    ]) !!}
            </div>

            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="enquiry-email" class="block text-sm font-medium text-gray-700 mb-1">
                Enquiry Email
            </label>

            <div class="relative">
                {!! html()->input('email', 'enquiry-email', $settings['Contact Us Settings']['enquiry-email']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('enquiry-email'),
                        'border-red-500' => $errors->has('enquiry-email'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['enquiry-email']['placeholder'],
                        'required' => false,
                        'id' => 'enquiry-email',
                    ]) !!}
            </div>

            @error('enquiry-email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="complaint-email" class="block text-sm font-medium text-gray-700 mb-1">
                Complaint Email
            </label>

            <div class="relative">
                {!! html()->input('email', 'complaint-email', $settings['Contact Us Settings']['complaint-email']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('complaint-email'),
                        'border-red-500' => $errors->has('complaint-email'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['complaint-email']['placeholder'],
                        'required' => false,
                        'id' => 'complaint-email',
                    ]) !!}
            </div>

            @error('complaint-email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="feedback-email" class="block text-sm font-medium text-gray-700 mb-1">
                Feedback Email
            </label>

            <div class="relative">
                {!! html()->input('email', 'feedback-email', $settings['Contact Us Settings']['feedback-email']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('feedback-email'),
                        'border-red-500' => $errors->has('feedback-email'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Contact Us Settings']['feedback-email']['placeholder'],
                        'required' => false,
                        'id' => 'feedback-email',
                    ]) !!}
            </div>

            @error('feedback-email')
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

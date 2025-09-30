{{-- Communication Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-envelope class="h-5 w-5 text-green-600" />
        <h3 class="text-lg font-bold text-gray-900">Communication Settings</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="sms_gateway" class="block text-sm font-medium text-gray-700 mb-1">
                SMS Gateway
            </label>

            <div class="relative">
                {!! html()->input('text', 'sms_gateway', $settings['Communication Settings']['sms_gateway']['setting_value'])->class([
                        'w-full  pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('sms_gateway'),
                        'border-red-500' => $errors->has('sms_gateway'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Communication Settings']['sms_gateway']['placeholder'],
                        'required' => true,
                        'id' => 'sms_gateway',
                    ]) !!}
            </div>

            @error('sms_gateway')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="twilio_sid" class="block text-sm font-medium text-gray-700 mb-1">
                Twilio SID
                <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'twilio_sid', $settings['Communication Settings']['twilio_sid']['setting_value'])->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('twilio_sid'),
                        'border-red-500' => $errors->has('twilio_sid'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Communication Settings']['twilio_sid']['placeholder'],
                        'required' => true,
                        'id' => 'twilio_sid',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="twilio_sid" aria-label="Show passcode" aria-controls="twilio_sid">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="twilio_sid"
                    data-verify-url=""
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('twilio_sid')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="twilio_auth_token" class="block text-sm font-medium text-gray-700 mb-1">
                Twilio Auth Token
                <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'twilio_auth_token', $settings['Communication Settings']['twilio_auth_token']['setting_value'])->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('twilio_auth_token'),
                        'border-red-500' => $errors->has('twilio_auth_token'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Communication Settings']['twilio_auth_token']['placeholder'],
                        'required' => true,
                        'id' => 'twilio_auth_token',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="twilio_auth_token" aria-label="Show passcode" aria-controls="twilio_auth_token">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="twilio_auth_token"
                    data-verify-url=""
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('twilio_auth_token')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="twilio_from_number" class="block text-sm font-medium text-gray-700 mb-1">
                Twilio From Number
                <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'twilio_from_number', $settings['Communication Settings']['twilio_from_number']['setting_value'])->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('twilio_from_number'),
                        'border-red-500' => $errors->has('twilio_from_number'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Communication Settings']['twilio_from_number']['placeholder'],
                        'required' => true,
                        'id' => 'twilio_from_number',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="twilio_from_number" aria-label="Show passcode" aria-controls="twilio_from_number">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="twilio_from_number"
                    data-verify-url=""
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('twilio_from_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="twilio_messaging_service_sid" class="block text-sm font-medium text-gray-700 mb-1">
                Twilio Messaging Service SID
                <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'twilio_messaging_service_sid', $settings['Communication Settings']['twilio_messaging_service_sid']['setting_value'])->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('twilio_messaging_service_sid'),
                        'border-red-500' => $errors->has('twilio_messaging_service_sid'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Communication Settings']['twilio_messaging_service_sid']['placeholder'],
                        'required' => true,
                        'id' => 'twilio_messaging_service_sid',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="twilio_messaging_service_sid" aria-label="Show passcode" aria-controls="twilio_messaging_service_sid">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="twilio_messaging_service_sid"
                    data-verify-url=""
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('twilio_messaging_service_sid')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="sms_test_mode" class="block text-sm font-medium text-gray-700 mb-1">
                SMS Test Mode
            </label>

            <div class="relative">
                <select name="sms_test_mode" id="sms_test_mode"
                    class="w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500 {{ $errors->has('sms_test_mode') ? 'border-red-500' : 'border-gray-300' }}"
                    required>
                    <option value="Yes" {{ old('sms_test_mode', $settings['Communication Settings']['sms_test_mode']['setting_value']) == 'Yes' ? 'selected' : '' }}>Yes</option>
                    <option value="No" {{ old('sms_test_mode', $settings['Communication Settings']['sms_test_mode']['setting_value']) == 'No' ? 'selected' : '' }}>No</option>
                </select>
            </div>

            @error('sms_test_mode')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
{{-- Communication Settings --}}

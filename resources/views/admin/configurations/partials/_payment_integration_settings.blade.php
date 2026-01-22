{{-- Payment Integration Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-credit-card class="h-5 w-5 text-indigo-600" />
        <h3 class="text-lg font-bold text-gray-900">Payment Integration</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Payment Gateway --}}
        <div>
            <label for="payment_gateway" class="block text-sm font-medium text-gray-700 mb-1">
                Payment Gateway
            </label>
            <div class="relative">
                {!! html()->input(
                        'text',
                        'payment_gateway',
                        old('payment_gateway', $settings['Payment Settings']['payment_gateway']['setting_value'] ?? ''),
                    )->class([
                        'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('payment_gateway'),
                        'border-red-500' => $errors->has('payment_gateway'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => 'Stripe / PayPal / Razorpay',
                        'id' => 'payment_gateway',
                        'disabled' => true,
                    ]) !!}

                {{-- Lock --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="payment_gateway" aria-haspopup="dialog" aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('payment_gateway')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Public Key --}}
        <div>
            <label for="payment_api_public_key" class="block text-sm font-medium text-gray-700 mb-1 required">
                Public Client Key
            </label>
            <div class="relative">
                {!! html()->input(
                        'password',
                        'payment_api_public_key',
                        old('payment_api_public_key', $settings['Payment Settings']['payment_api_public_key']['setting_value'] ? '************' : ''),
                    )->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('payment_api_public_key'),
                        'border-red-500' => $errors->has('payment_api_public_key'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => 'Enter public API key',
                        'id' => 'payment_api_public_key',
                        'disabled' => true,
                    ]) !!}

                {{-- Eye toggle --}}
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="payment_api_public_key" aria-label="Show API key">
                    <x-heroicon-o-eye data-eye-off class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye class="w-5 h-5 hidden" />
                </button>

                {{-- Lock --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="payment_api_public_key" aria-haspopup="dialog"
                    aria-controls="verify-modal" aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('payment_api_public_key')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>


        {{-- API Key (Encrypted) --}}
        <div>
            <label for="payment_api_key" class="block text-sm font-medium text-gray-700 mb-1 required">
                API Login ID
            </label>
            <div class="relative">
                {!! html()->input(
                        'password',
                        'payment_api_key',
                        old('payment_api_key', $settings['Payment Settings']['payment_api_key']['setting_value'] ? '************' : ''),
                    )->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('payment_api_key'),
                        'border-red-500' => $errors->has('payment_api_key'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => 'Encrypted API Key',
                        'id' => 'payment_api_key',
                        'disabled' => true,
                    ]) !!}

                {{-- Eye toggle --}}
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="payment_api_key" aria-label="Show API key">
                    <x-heroicon-o-eye data-eye-off class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye class="w-5 h-5 hidden" />
                </button>

                {{-- Lock --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="payment_api_key" aria-haspopup="dialog" aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('payment_api_key')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- API Secret Key (*) --}}
        <div>
            <label for="payment_api_secret" class="block text-sm font-medium text-gray-700 mb-1 required">
                Transaction Key
            </label>
            <div class="relative">
                {!! html()->input(
                        'password',
                        'payment_api_secret',
                        old('payment_api_secret', $settings['Payment Settings']['payment_api_secret']['setting_value'] ? '************' : ''),
                    )->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('payment_api_secret'),
                        'border-red-500' => $errors->has('payment_api_secret'),
                    ])->attributes([
                        'autocomplete' => 'off',
                        'placeholder' => 'Encrypted Secret Key',
                        'id' => 'payment_api_secret',
                        'disabled' => true,
                    ]) !!}

                {{-- Eye toggle --}}
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="payment_api_secret" aria-label="Show Secret key">
                    <x-heroicon-o-eye data-eye-off class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye class="w-5 h-5 hidden" />
                </button>

                {{-- Lock --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="payment_api_secret" aria-haspopup="dialog"
                    aria-controls="verify-modal" aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('payment_api_secret')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Test Mode --}}
        <div>
            <label for="payment_test_mode" class="block text-sm font-medium text-gray-700 mb-1">
                Payment Test Mode
            </label>
            <div class="relative">
                <select name="payment_test_mode" id="payment_test_mode"
                    class="w-full pl-3 pr-10 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500"
                    disabled>
                    <option value="1"
                        {{ old('payment_test_mode', $settings['Payment Settings']['payment_test_mode']['setting_value'] ?? '') == 1 ? 'selected' : '' }}>
                        Testing Only</option>
                    <option value="0"
                        {{ old('payment_test_mode', $settings['Payment Settings']['payment_test_mode']['setting_value'] ?? '') == 0 ? 'selected' : '' }}>
                        Live Card Processing</option>
                </select>

                {{-- Lock --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="payment_test_mode" aria-haspopup="dialog"
                    aria-controls="verify-modal" aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('payment_test_mode')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Security Notice --}}
    <div class="mt-4 rounded-md p-4 bg-amber-50 border border-amber-200 text-amber-900">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-lock-closed class="w-8 h-8 text-amber-600" />
            <div>
                <h4 class="text-sm font-medium text-amber-800">Security Notice</h4>
                <p class="text-sm mt-1 text-amber-700">
                    Payment integration settings contain sensitive API keys and credentials.
                    Master Passcode verification is required to view or modify these settings for security purposes.
                </p>
            </div>
        </div>
    </div>
</div>
{{-- End Payment Integration --}}

{{-- Admin Settings (Tailwind + Vanilla JS) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-lock-closed class="h-5 w-5 text-blue-600" aria-hidden="true" />
        <h3 class="text-lg font-bold text-gray-900">Admin Settings</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Admin Code --}}
        <div>
            <label for="master_passcode" class="text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                <span>
                    Admin Code
                    <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
                </span>

                <span class="relative group inline-flex items-center">
                    <x-heroicon-o-information-circle class="w-4 h-4 text-blue-400 hover:text-blue-500 cursor-pointer" />
                    <span class="tooltip-panel tooltip-panel--right max-w-sm">
                        Admin Code - Secure code used to authorize Tax-Exempt purchases and Master Login access on the Checkout page.
                    </span>
                </span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'master_passcode', $settings['Admin Settings']['master_passcode']['setting_value'] ? '********' : null)->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('master_passcode'),
                        'border-red-500' => $errors->has('master_passcode'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Admin Settings']['master_passcode']['placeholder'],
                        'required' => true,
                        'id' => 'master_passcode',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="master_passcode" aria-label="Show passcode" aria-controls="master_passcode">
                    <x-heroicon-o-eye data-eye-off class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="master_passcode"
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('master_passcode')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Master Password Entry --}}
        <div>
            <label for="master_password_entry" class="text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                <span>
                    Master Password - Entry
                    <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
                </span>

                <span class="relative group inline-flex items-center">
                    <x-heroicon-o-information-circle class="w-4 h-4 text-blue-400 hover:text-blue-500 cursor-pointer" />
                    <span class="tooltip-panel tooltip-panel--right max-w-sm">
                        Master Password - Secure password required to modify any administrative settings.
                    </span>
                </span>
            </label>

            <div class="relative">
                {!! html()->input('password', 'master_password_entry', $settings['Admin Settings']['master_password_entry']['setting_value'] ? '********' : null)->class([
                        'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                        'border-gray-300' => !$errors->has('master_password_entry'),
                        'border-red-500' => $errors->has('master_password_entry'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'autocomplete' => 'off',
                        'placeholder' => $settings['Admin Settings']['master_password_entry']['placeholder'],
                        'required' => true,
                        'id' => 'master_password_entry',
                        'disabled' => true, // <-- disabled by default
                    ]) !!}

                <!-- Eye toggle -->
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="master_password_entry" aria-label="Show password" aria-controls="master_password_entry">
                    <x-heroicon-o-eye data-eye-off class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye class="w-5 h-5 hidden" />
                </button>

                <!-- lock button triggers modal -->
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="master_password_entry"
                    aria-haspopup="dialog"
                    aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" aria-hidden="true" />
                </button>
            </div>

            @error('master_password_entry')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Security Notice --}}
        <div class="mt-4 rounded-md p-4 md:col-span-2 text-left bg-amber-50 border border-amber-200 text-amber-900">
            <div class="flex items-start space-x-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 mt-0.5" />
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Security Notice</h4>
                    <p class="text-sm mt-1 text-amber-700">
                        Both fields are critical for system security. The Admin Code is encrypted and the Master Password is required to access/edit it. Always use strong, unique passwords and store them securely.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

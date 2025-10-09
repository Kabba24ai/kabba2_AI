{{-- Mail Send Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-envelope class="h-5 w-5 text-green-600" />
        <h3 class="text-lg font-bold text-gray-900">Mail Send Settings</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Mail Mailer --}}
        <div>
            <label for="mail_mailer" class="block text-sm font-medium text-gray-700 mb-1">
                Mail Mailer
            </label>
            {!! html()->input('text', 'mail_mailer', old('mail_mailer', $settings['Mail Send Settings']['mail_mailer']['setting_value'] ?? ''))
            ->class([
            'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
            'border-gray-300' => !$errors->has('mail_mailer'),
            'border-red-500' => $errors->has('mail_mailer'),
            ])
            ->attributes([
            'autocomplete' => 'off',
            'placeholder' => 'SMTP / Sendmail / Mailgun',
            'id' => 'mail_mailer',
            'required' => true,
            ]) !!}
            @error('mail_mailer')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail Host --}}
        <div>
            <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-1">
                Mail Host
            </label>
            {!! html()->input('text', 'mail_host', old('mail_host', $settings['Mail Send Settings']['mail_host']['setting_value'] ?? ''))
            ->class([
            'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
            'border-gray-300' => !$errors->has('mail_host'),
            'border-red-500' => $errors->has('mail_host'),
            ])
            ->attributes([
            'autocomplete' => 'off',
            'placeholder' => 'Mail server host',
            'id' => 'mail_host',
            'required' => true,
            ]) !!}
            @error('mail_host')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail Port --}}
        <div>
            <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1">
                Mail Port
            </label>
            {!! html()->input('number', 'mail_port', old('mail_port', $settings['Mail Send Settings']['mail_port']['setting_value'] ?? ''))
            ->class([
            'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
            'border-gray-300' => !$errors->has('mail_port'),
            'border-red-500' => $errors->has('mail_port'),
            ])
            ->attributes([
            'autocomplete' => 'off',
            'placeholder' => '465',
            'id' => 'mail_port',
            'required' => true,
            ]) !!}
            @error('mail_port')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail Username (Encrypted) --}}
        <div>
            <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1">
                Mail Username <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>
            <div class="relative">
                {!! html()->input('password', 'mail_username', old('mail_username', $settings['Mail Send Settings']['mail_username']['setting_value'] ?? ''))
                ->class([
                'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                'border-gray-300' => !$errors->has('mail_username'),
                'border-red-500' => $errors->has('mail_username'),
                ])
                ->attributes([
                'autocomplete' => 'off',
                'placeholder' => 'Encrypted Username',
                'id' => 'mail_username',
                'disabled' => true,
                ]) !!}

                {{-- Eye toggle --}}
                <button type="button" class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="mail_username" aria-label="Show passcode">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                {{-- Lock button --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="mail_username" data-verify-url="{{ route('admin.configurations.verifyMaster') }}" aria-haspopup="dialog" aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('mail_username')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail Password (Encrypted) --}}
        <div>
            <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1">
                Mail Password <span class="text-xs text-blue-600 ml-1">(Encrypted)</span>
            </label>
            <div class="relative">
                {!! html()->input('password', 'mail_password', old('mail_password', $settings['Mail Send Settings']['mail_password']['setting_value'] ?? ''))
                ->class([
                'w-full pr-12 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
                'border-gray-300' => !$errors->has('mail_password'),
                'border-red-500' => $errors->has('mail_password'),
                ])
                ->attributes([
                'autocomplete' => 'off',
                'placeholder' => 'Encrypted Password',
                'id' => 'mail_password',
                'disabled' => true,
                ]) !!}

                {{-- Eye toggle --}}
                <button type="button" class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="mail_password" aria-label="Show password">
                    <x-heroicon-o-eye data-eye class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye-off class="w-5 h-5 hidden" />
                </button>

                {{-- Lock button --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-verify-url="{{ route('admin.configurations.verifyMaster') }}" data-field-id="mail_password" aria-haspopup="dialog" aria-controls="verify-modal"
                    aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            @error('mail_password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail Encryption --}}
        <div>
            <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1">
                Mail Encryption
            </label>
            {!! html()->input('text', 'mail_encryption', old('mail_encryption', $settings['Mail Send Settings']['mail_encryption']['setting_value'] ?? ''))
            ->class([
            'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
            'border-gray-300' => !$errors->has('mail_encryption'),
            'border-red-500' => $errors->has('mail_encryption'),
            ])
            ->attributes([
            'autocomplete' => 'off',
            'placeholder' => 'tls / ssl / none',
            'id' => 'mail_encryption',
            'required' => true,
            ]) !!}
            @error('mail_encryption')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail From Address --}}
        <div>
            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1">
                Mail From Address
            </label>
            {!! html()->input('text', 'mail_from_address', old('mail_from_address', $settings['Mail Send Settings']['mail_from_address']['setting_value'] ?? ''))
            ->class([
            'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
            'border-gray-300' => !$errors->has('mail_from_address'),
            'border-red-500' => $errors->has('mail_from_address'),
            ])
            ->attributes([
            'autocomplete' => 'off',
            'placeholder' => 'from@example.com',
            'id' => 'mail_from_address',
            'required' => true,
            ]) !!}
            @error('mail_from_address')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mail From Name --}}
        <div>
            <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1">
                Mail From Name
            </label>
            {!! html()->input('text', 'mail_from_name', old('mail_from_name', $settings['Mail Send Settings']['mail_from_name']['setting_value'] ?? ''))
            ->class([
            'w-full pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500',
            'border-gray-300' => !$errors->has('mail_from_name'),
            'border-red-500' => $errors->has('mail_from_name'),
            ])
            ->attributes([
            'autocomplete' => 'off',
            'placeholder' => 'Your App Name',
            'id' => 'mail_from_name',
            'required' => true,
            ]) !!}
            @error('mail_from_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
{{-- End Mail Send Settings --}}
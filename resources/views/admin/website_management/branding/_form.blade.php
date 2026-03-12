{{-- Profile Settings --}}
<form action="{{ route('admin.configurations.save-profile-settings') }}" method="POST" enctype="multipart/form-data" data-parsley-validate>
    @csrf
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center space-x-2 mb-4">
            <x-heroicon-o-user class="h-5 w-5 text-blue-600" aria-hidden="true" />
            <h3 class="text-lg font-bold text-gray-900">Profile Settings</h3>
        </div>
        @php
            $logo = \App\Helpers\ConfigurationHelper::getProfileLogo();

        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


            {{-- Logo Upload --}}
            <div class="md:col-span-2">
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">
                    Logo
                </label>

                <div class="flex items-center space-x-4">
                    @if (!empty($logo))
                        <img src="{{ $logo }}" alt="Logo"
                            class="h-14 w-14 object-contain border rounded-md p-1 bg-gray-50">
                    @endif

                    {!! html()->file('logo')->class([
                            'block w-full text-sm text-gray-500',
                            'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                            'file:text-sm file:font-semibold',
                            'file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                            'border border-gray-300 rounded-md',
                            'border-red-500' => $errors->has('logo'),
                        ])->attributes([
                            'id' => 'logo',
                            'accept' => 'image/*',
                        ]) !!}
                </div>

                @error('logo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Name --}}
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name
                </label>

                {!! html()->text('name', old('name', $settings['Profile Settings']['name']['setting_value'] ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-3 text-sm shadow-sm focus:ring-2',
                        'border-red-500' => $errors->has('name'),
                        'border-gray-300' => !$errors->has('name'),
                    ])->attributes([
                        'placeholder' => 'Enter Name',
                        'id' => 'name',
                        'autocomplete' => 'off',
                    ]) !!}

                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address
                </label>

                {!! html()->email('email', old('email', $settings['Profile Settings']['email']['setting_value'] ?? ''))->class([
                        'w-full rounded-md border focus:outline-none px-3 py-3 text-sm shadow-sm focus:ring-2',
                        'border-red-500' => $errors->has('email'),
                        'border-gray-300' => !$errors->has('email'),
                    ])->attributes([
                        'placeholder' => 'Enter Email',
                        'id' => 'email',
                        'autocomplete' => 'off',
                        'data-parsley-type' => 'email',
                        'data-parsley-trigger' => 'change',
                    ]) !!}

                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone Number --}}
            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number
                </label>

                {!! html()->text('phone', old('phone', $settings['Profile Settings']['phone']['setting_value'] ?? ''))->class([
                        'masked-phone w-full border rounded-md px-3 py-3 text-sm shadow-sm focus:outline-none focus:ring-2',
                        'border-red-500' => $errors->has('phone'),
                        'border-gray-300' => !$errors->has('phone'),
                    ])->attributes([
                        'maxlength' => 14,
                        'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                        'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                        'placeholder' => '(xxx) xxx-xxxx',
                        'id' => 'phone',
                        'autocomplete' => 'tel',
                    ]) !!}

                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Save Button --}}
        <div class="my-3 flex justify-end">
            <button type="submit" class="flex items-center space-x-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save w-4 h-4 mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Save</span>
            </button>
        </div>
    </div>
</form>

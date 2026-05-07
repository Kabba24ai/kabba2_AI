{{-- Application Code Settings --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">

    <div class="flex items-center space-x-2 mb-4">
        <x-heroicon-o-key class="h-5 w-5 text-blue-600" />

        <h3 class="text-lg font-bold text-gray-900">
            Application Code Settings
        </h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

        {{-- Application Code --}}
        <div>

            <label for="application_code" class="block text-sm font-medium text-gray-700 mb-1">
                Application Code
            </label>

            <div class="relative">

                {!! html()->input(
                    'text',
                    'application_code',
                    old(
                        'application_code',
                        $currentApplicationCode ?? ''
                    ),
                )->class([
                    'w-full pr-10 pl-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors uppercase disabled:bg-gray-50 disabled:text-gray-500',
                    'border-gray-300' => !$errors->has('application_code'),
                    'border-red-500' => $errors->has('application_code'),
                ])->attributes([
                    'autocomplete' => 'off',
                    'maxlength' => 6,
                    'placeholder' => 'ABC123',
                    'id' => 'application_code',
                    'disabled' => true,
                    'style' => 'text-transform: uppercase;',
                    'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();",
                ]) !!}

               

                {{-- Lock Button --}}
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify
                    data-field-id="application_code"
                    data-no-fill="true"
                    aria-haspopup="dialog"
                    aria-controls="verify-modal" 
                    aria-label="Verify to edit">

                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>

            </div>

            @error('application_code')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>

    </div>

    {{-- Notice --}}
    <div class="mt-4 rounded-md p-4 bg-blue-50 border border-blue-200">

        <div class="flex items-start gap-3">

            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />

            <div>

                <p class="text-sm font-medium text-blue-800">
                    Important Notice
                </p>

                <p class="text-sm text-blue-700 mt-1">
                    Application Code is protected and requires master verification before editing.
                </p>

                <p class="text-sm text-blue-700 mt-1">
                    Must contain exactly 6 alphanumeric characters.
                </p>

            </div>

        </div>

    </div>

</div>
{{-- End Application Code Settings --}}
<!-- Basic Info -->
<div class="grid grid-cols-3 gap-6">
    <!-- Store Name Input -->
    <div>
        <label for="store_name" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">
            Store Name
        </label>

        {!! html()->text('store_name', old('store_name', $store->store_name ?? null))->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-gray-300' => !$errors->has('store_name'),
                'border-red-500' => $errors->has('store_name'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Enter Store Name',
                'autocomplete' => 'off',
                'id' => 'store_name',
            ])->required() !!}

        @error('store_name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone"
            class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Phone</label>
        {!! html()->text('phone', old('phone'))->class([
                'masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                'border-red-500' => $errors->has('phone'),
            ])->attributes([
                'placeholder' => '(xxx) xxx-xxxx',
                'id' => 'phone',
                'autocomplete' => 'tel',
            ])->required() !!}

        @error('phone')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email"
            class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Email</label>
        {!! html()->email('email', old('email', $store->email ?? null))->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                'border-gray-300' => !$errors->has('email'),
                'border-red-500' => $errors->has('email'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Enter Email',
                'autocomplete' => 'off',
                'id' => 'email',
            ])->required() !!}

        @error('email')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>


    <!-- Store Code and Status in one row -->
    <div class="col-span-3 grid grid-cols-2 gap-6">
        <div>
            <label for="state_id"
                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">State</label>

            {!! html()->select('state_id', $states, old('state_id', $store->state_id ?? null))->class([
                    'choices-select w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('state_id'),
                ])->attributes([
                    'data-placeholder' => 'Select State',
                    'id' => 'state_id',
                    'data-parsley-errors-container' => '#state_id-errors',
                ])->required() !!}

            @error('state_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            <div id="state_id-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
        </div>

        <div>
            <label for="city"
                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">City</label>
            {!! html()->text('city', old('city', $store->city ?? null))->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-gray-300' => !$errors->has('city'),
                    'border-red-500' => $errors->has('city'),
                ])->attributes([
                    'maxlength' => 240,
                    'data-parsley-maxlength' => 240,
                    'placeholder' => 'Enter City',
                    'autocomplete' => 'off',
                    'id' => 'city',
                ])->required() !!}

            @error('city')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="address"
                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Address</label>
            {!! html()->text('address', old('address', $store->address ?? null))->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-gray-300' => !$errors->has('address'),
                    'border-red-500' => $errors->has('address'),
                ])->attributes([
                    'maxlength' => 240,
                    'data-parsley-maxlength' => 240,
                    'placeholder' => 'Enter Address',
                    'autocomplete' => 'off',
                    'id' => 'address',
                ])->required() !!}

            @error('address')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="zip_code" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Zip
                Code</label>
            {!! html()->text('zip_code', old('zip_code', $store->zip_code ?? null))->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    'border-gray-300' => !$errors->has('zip_code'),
                    'border-red-500' => $errors->has('zip_code'),
                ])->attributes([
                    'maxlength' => 20,
                    'data-parsley-maxlength' => 20,
                    'placeholder' => 'Enter Zip Code',
                    'autocomplete' => 'off',
                    'id' => 'zip_code',
                ])->required() !!}

            @error('zip_code')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="is_primary" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Is
                Primary</label>
            {{ html()->select('is_primary', ['Yes' => 'Yes', 'No' => 'No'], old('is_primary', $store->is_primary ?? 'No'))->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('is_primary'),
                ])->required()->placeholder('Please Select') }}
            @error('is_primary')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status"
                class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Status</label>
            {{ html()->select(
                    'status',
                    ['Active' => 'Active', 'Inactive' => 'InActive', 'Archived' => 'Archived'],
                    old('status', $store->status ?? 'Active'),
                )->class([
                    'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                    'border-red-500' => $errors->has('status'),
                ])->required()->placeholder('Please Select') }}
            @error('status')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>




@push('js')
@endpush

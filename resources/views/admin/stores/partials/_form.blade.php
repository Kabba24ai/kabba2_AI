{{-- Store Form --}}
<div class="space-y-8">

    {{-- Basic Info --}}
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Basic Info</h3>
        <hr class="mb-6 border-gray-300 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Store Name --}}
            <div>
                <label for="store_name" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Store Name
                </label>
                {!! html()->text('store_name', old('store_name', $store->store_name ?? null))->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('store_name'),
                        'border-red-500' => $errors->has('store_name'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter store name',
                        'autocomplete' => 'off',
                        'id' => 'store_name',
                    ])->required() !!}
                @error('store_name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Phone Number
                </label>
                {{ html()->text('phone', old('phone', $store->phone ?? null))->class(
                        'masked-phone w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    )->attributes([
                        'maxlength' => 240,
                        'placeholder' => '(xxx) xxx-xxxx',
                        'autocomplete' => 'off',
                        'id' => 'phone',
                    ])->required() }}
                @error('phone')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Email Address
                </label>
                {!! html()->email('email', old('email', $store->email ?? null))->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('email'),
                        'border-red-500' => $errors->has('email'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'store@company.com',
                        'autocomplete' => 'off',
                        'id' => 'email',
                    ])->required() !!}
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

             {{-- Details --}}
            <div class="md:col-span-3">
                <label for="details" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Details
                </label>
                {!! html()->text('details', old('details', $store->details ?? null))->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('details'),
                        'border-red-500' => $errors->has('details'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter any additional details about the store',
                        'autocomplete' => 'off',
                        'id' => 'details',
                    ])->required() !!}
                @error('details')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Store Address --}}
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Store Address</h3>
        <hr class="mb-6 border-gray-300 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            {{-- Address --}}
            <div class="md:col-span-4">
                <label for="address" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Street Address
                </label>
                {!! html()->text('address', old('address', $store->address ?? null))->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('address'),
                        'border-red-500' => $errors->has('address'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => '123 Main Street',
                        'autocomplete' => 'off',
                        'id' => 'address',
                    ])->required() !!}
                @error('address')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- City --}}
            <div>
                <label for="city"
                    class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">City</label>
                {!! html()->text('city', old('city', $store->city ?? null))->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('city'),
                        'border-red-500' => $errors->has('city'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter city',
                        'autocomplete' => 'address-level2',
                        'id' => 'city',
                    ])->required() !!}
                @error('city')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- State --}}
            <div>
                <label for="state_id" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    State
                </label>
                {!! html()->select('state_id', $states, old('state_id', $store->state_id ?? null))->class([
                        'choices-select w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('state_id'),
                        'border-red-500' => $errors->has('state_id'),
                    ])->attributes([
                        'data-placeholder' => 'Select State',
                        'id' => 'state_id',
                        'data-parsley-errors-container' => '#state_id-errors',
                    ])->required() !!}
                <div id="state_id-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                @error('state_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Zip --}}
            <div>
                <label for="zip_code" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Zip Code
                </label>
                {{ html()->number('zip_code', old('zip_code', $store->zip_code ?? null))->class(
                        'w-full rounded-lg border border-gray-300 px-4 py-2  shadow-sm text-sm focus:border-gray-900 focus:outline-none',
                    )->attributes([
                        'maxlength' => 8,
                        'placeholder' => 'Zip code',
                        'autocomplete' => 'off',
                        'data-parsley-type' => 'number',
                        'id' => 'zip_code',
                    ])->required() }}
                @error('zip_code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="country"
                    class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">Country</label>
                {!! html()->text('country', old('country', $store->country ?? "USA"))->class([
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                        'border-gray-300' => !$errors->has('country'),
                        'border-red-500' => $errors->has('country'),
                    ])->attributes([
                        'maxlength' => 240,
                        'data-parsley-maxlength' => 240,
                        'placeholder' => 'Enter country',
                        'autocomplete' => 'address-level2',
                        'id' => 'country',
                    ])->required() !!}
                @error('country')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Latitude --}}
            <div>
                <label for="latitude" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Latitude
                </label>
                {{ html()->text('latitude', old('latitude', $store->latitude ?? null))->class(
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    )->attributes([
                        'maxlength' => 50,
                        'placeholder' => 'Enter latitude',
                        'autocomplete' => 'off',
                        'id' => 'latitude',
                    ])->required() }}
                @error('latitude')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Longitude --}}
            <div>
                <label for="longitude" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Longitude
                </label>
                {{ html()->text('longitude', old('longitude', $store->longitude ?? null))->class(
                        'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white',
                    )->attributes([
                        'maxlength' => 50,
                        'placeholder' => 'Enter longitude',
                        'autocomplete' => 'off',
                        'id' => 'longitude',
                    ])->required() }}
                @error('longitude')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Store Settings --}}
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Store Settings</h3>
        <hr class="mb-6 border-gray-300 dark:border-gray-700">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            {{-- Primary vs Alternate (radio) --}}
            <div>
                <span class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Store Designation
                </span>

                @php $isPrimary = old('is_primary', ($store->is_primary ?? 'No')) @endphp

                <div class="space-y-2">
                    <label class="flex items-center gap-3">
                        <input type="radio" name="is_primary" value="Yes" @checked($isPrimary === 'Yes')
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-800 dark:text-gray-200">Primary Store</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="radio" name="is_primary" value="No" @checked($isPrimary !== 'Yes')
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-800 dark:text-gray-200">Alternate Store</span>
                    </label>
                </div>

                @error('is_primary')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status (toggle + select fallback for submission) --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                    Store Status
                </label>

                @php $status = old('status', $store->status ?? 'Active') @endphp

                <div x-data="{ on: @js($status === 'Active') }" class="flex items-center gap-3">
                    <button type="button" @click="on = !on; $refs.status.value = on ? 'Active' : 'Inactive'"
                        :aria-pressed="on"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition
                                   bg-gray-200 data-[on=true]:bg-green-500"
                        :data-on="on">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                            :class="on ? 'translate-x-5' : 'translate-x-1'"></span>
                    </button>
                    <span class="text-sm text-gray-800 dark:text-gray-200" x-text="on ? 'Active' : 'Inactive'"></span>

                    {{-- real form control --}}
                    <select x-ref="status" name="status" class="hidden">
                        <option value="Active" @selected($status === 'Active')>Active</option>
                        <option value="Inactive" @selected($status === 'Inactive')>Inactive</option>
                    </select>
                </div>

                @error('status')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>



@push('js')
@endpush

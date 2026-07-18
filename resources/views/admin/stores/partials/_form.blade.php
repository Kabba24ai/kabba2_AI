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

            {{-- Country: hidden from UI, always USA — used only by AI/schema layer --}}
            <div class="hidden">
                <select name="country" id="country">
                    <option value="USA" selected>United States (USA)</option>
                </select>
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
                        <input type="radio" name="is_primary" value="Yes" @checked($isPrimary==='Yes' )
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-800 dark:text-gray-200">Primary Store</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="radio" name="is_primary" value="No" @checked($isPrimary !=='Yes' )
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
                        <option value="Active" @selected($status==='Active' )>Active</option>
                        <option value="Inactive" @selected($status==='Inactive' )>Inactive</option>
                    </select>
                </div>

                @error('status')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

           

        </div>
    </div>

     {{-- Store Lunch Start Time --}}
            <div class="hidden">
                
                <span for="lunch_start_time" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300 required">
                   Store Lunch Start Time
                </span>

                @php
                    $lunchStart = old(
                        'lunch_start_time',
                        isset($store->lunch_start_time)
                            ? \Carbon\Carbon::parse($store->lunch_start_time)->format('g:i A')
                            : ''
                    );
                @endphp

                <div class="relative w-full md:w-48">
                    <input
                        type="text"
                        name="lunch_start_time"
                        id="lunch_start_time"
                        value="{{ $lunchStart }}"
                        class="timepicker w-full border rounded-md px-3 py-2 text-sm shadow-sm pr-10 focus:outline-none focus:ring-2 bg-white text-gray-700 border-gray-300"
                        data-format="HH:mm"
                        autocomplete="off"
                        placeholder="Select time"
                    />

                    <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-800" />
                    </span>
                </div>  
                @error('lunch_start_time')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                {{-- Note --}}
                <p class="mt-2 text-xs text-gray-500">
                  <strong> Note:-</strong> <span class="text-gray-500" >  All employees scheduled in this store will automatically start their lunch at this time.
                    Lunch duration is controlled from Time Tracker Settings. Employees will be automatically clocked out for lunch and clocked back in after the duration ends.
                    </span>
                </p>

              
            </div>

    {{-- ═══════════════════════════════════════════════════════ Service Area Settings ══ --}}
    <div>
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Service Area Settings</h3>
        <hr class="mb-6 border-gray-300 dark:border-gray-700">

        @php
            $storeServiceAreas  = $store->serviceAreas ?? collect();
            $existingRadius     = $storeServiceAreas->where('area_group', 'radius')->first();

            $includedAreas = old('service_areas_included')
                ? collect(old('service_areas_included'))->map(fn($a) => (object) $a)
                : $storeServiceAreas->where('area_group', 'included')->values();

            $excludedAreas = old('service_areas_excluded')
                ? collect(old('service_areas_excluded'))->map(fn($a) => (object) $a)
                : $storeServiceAreas->where('area_group', 'excluded')->values();

            $radiusEnabled  = old('service_area_enable_radius',   $existingRadius ? '1' : '0');
            $radiusMiles    = old('service_area_radius_miles',     $existingRadius?->radius_miles ?? '');
            $radiusDelivery = old('service_area_delivery_allowed', $existingRadius ? ($existingRadius->delivery_allowed ? '1' : '0') : '1');
            $radiusPickup   = old('service_area_pickup_allowed',   $existingRadius ? ($existingRadius->pickup_allowed   ? '1' : '0') : '1');
        @endphp

        {{-- 1. Default Service Radius --}}
        <div class="p-6 bg-white rounded-lg shadow border mb-6"
             x-data="{ radiusOn: @js($radiusEnabled === '1') }">
            <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">Default Service Radius</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Service Radius</label>
                    <div class="flex items-center gap-3">
                        <button type="button"
                            @click="radiusOn = !radiusOn; $refs.radiusEnable.value = radiusOn ? '1' : '0'"
                            :aria-pressed="radiusOn"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition bg-gray-200 data-[on=true]:bg-green-500"
                            :data-on="radiusOn">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                                  :class="radiusOn ? 'translate-x-5' : 'translate-x-1'"></span>
                        </button>
                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="radiusOn ? 'Enabled' : 'Disabled'"></span>
                        <input type="hidden" name="service_area_enable_radius" x-ref="radiusEnable" value="{{ $radiusEnabled }}">
                    </div>
                </div>

                <div>
                    <label for="service_area_radius_miles"
                           class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Radius Miles</label>
                    <input type="number"
                           name="service_area_radius_miles"
                           id="service_area_radius_miles"
                           value="{{ $radiusMiles }}"
                           step="0.1" min="0.1"
                           :disabled="!radiusOn"
                           class="w-full md:w-40 border rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 bg-white text-gray-700 border-gray-300 disabled:opacity-40"
                           placeholder="e.g. 25">
                    @error('service_area_radius_miles')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Based on store latitude / longitude</p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Delivery Allowed Inside Radius</label>
                    <div x-data="{ on: @js($radiusDelivery === '1') }" class="flex items-center gap-3">
                        <button type="button"
                            @click="on = !on; $refs.deliveryAllowed.value = on ? '1' : '0'"
                            :aria-pressed="on"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition bg-gray-200 data-[on=true]:bg-green-500"
                            :data-on="on">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                                  :class="on ? 'translate-x-5' : 'translate-x-1'"></span>
                        </button>
                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="on ? 'Yes' : 'No'"></span>
                        <input type="hidden" name="service_area_delivery_allowed" x-ref="deliveryAllowed" value="{{ $radiusDelivery }}">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Pickup Allowed Inside Radius</label>
                    <div x-data="{ on: @js($radiusPickup === '1') }" class="flex items-center gap-3">
                        <button type="button"
                            @click="on = !on; $refs.pickupAllowed.value = on ? '1' : '0'"
                            :aria-pressed="on"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition bg-gray-200 data-[on=true]:bg-green-500"
                            :data-on="on">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                                  :class="on ? 'translate-x-5' : 'translate-x-1'"></span>
                        </button>
                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="on ? 'Yes' : 'No'"></span>
                        <input type="hidden" name="service_area_pickup_allowed" x-ref="pickupAllowed" value="{{ $radiusPickup }}">
                    </div>
                </div>

            </div>
        </div>

        {{-- 2. Additional Covered Areas --}}
        <div class="p-6 bg-white rounded-lg shadow border mb-6">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Additional Covered Areas</h4>
                <button type="button" onclick="addServiceAreaRow('included')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    + Add Covered Area
                </button>
            </div>
            <p class="mb-4 text-xs text-gray-500">These locations are serviced even if they fall outside the default radius.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 border-b">
                        <tr>
                            <th class="pb-2 pr-3">Area Type</th>
                            <th class="pb-2 pr-3">Name</th>
                            <th class="pb-2 pr-3">City</th>
                            <th class="pb-2 pr-3">County</th>
                            <th class="pb-2 pr-3">State</th>
                            <th class="pb-2 pr-3">ZIP</th>
                            <th class="pb-2 pr-3 text-center">Delivery</th>
                            <th class="pb-2 pr-3 text-center">Pickup</th>
                            <th class="pb-2 pr-3">Notes</th>
                            <th class="pb-2 pr-3 text-center">Status</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody id="sa-included-rows">
                        @foreach ($includedAreas as $i => $area)
                            <tr class="sa-row border-b border-gray-100 align-top">
                                @include('admin.stores.partials._service_area_row', [
                                    'group'  => 'included',
                                    'i'      => $i,
                                    'area'   => $area,
                                    'states' => $states,
                                ])
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="sa-included-empty" class="py-4 text-center text-sm text-gray-400 {{ $includedAreas->isNotEmpty() ? 'hidden' : '' }}">
                    No covered areas added yet.
                </div>
            </div>

            {{-- Hidden JS template --}}
            <table class="hidden" id="sa-included-template">
                <tbody>
                    <tr class="sa-row border-b border-gray-100 align-top">
                        @include('admin.stores.partials._service_area_row', [
                            'group'  => 'included',
                            'i'      => '__IDX__',
                            'area'   => null,
                            'states' => $states,
                        ])
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 3. Excluded Areas --}}
        <div class="p-6 bg-white rounded-lg shadow border mb-6">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Excluded Areas</h4>
                <button type="button" onclick="addServiceAreaRow('excluded')"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    + Add Excluded Area
                </button>
            </div>
            <p class="mb-4 text-xs text-gray-500">These locations are blocked even if they fall inside the default radius or covered areas. Excluded areas always win.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-600 border-b">
                        <tr>
                            <th class="pb-2 pr-3">Area Type</th>
                            <th class="pb-2 pr-3">Name</th>
                            <th class="pb-2 pr-3">City</th>
                            <th class="pb-2 pr-3">County</th>
                            <th class="pb-2 pr-3">State</th>
                            <th class="pb-2 pr-3">ZIP</th>
                            <th class="pb-2 pr-3">Reason / Notes</th>
                            <th class="pb-2 pr-3 text-center">Status</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody id="sa-excluded-rows">
                        @foreach ($excludedAreas as $i => $area)
                            <tr class="sa-row border-b border-gray-100 align-top">
                                @include('admin.stores.partials._service_area_row', [
                                    'group'  => 'excluded',
                                    'i'      => $i,
                                    'area'   => $area,
                                    'states' => $states,
                                ])
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="sa-excluded-empty" class="py-4 text-center text-sm text-gray-400 {{ $excludedAreas->isNotEmpty() ? 'hidden' : '' }}">
                    No excluded areas added yet.
                </div>
            </div>

            {{-- Hidden JS template --}}
            <table class="hidden" id="sa-excluded-template">
                <tbody>
                    <tr class="sa-row border-b border-gray-100 align-top">
                        @include('admin.stores.partials._service_area_row', [
                            'group'  => 'excluded',
                            'i'      => '__IDX__',
                            'area'   => null,
                            'states' => $states,
                        ])
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
    {{-- ══════════════════════════════════════════════════════════════════════════════════ --}}

    <div class="p-6 bg-white rounded-lg shadow border">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
            Hours of Operation
        </h3>

        @php
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        @endphp

        <div class="space-y-4">

            <div class="flex items-center gap-4 font-semibold text-sm text-gray-600 border-b pb-2">

                <div class="w-20 text-center">Day</div>

                <div class="w-24 text-center">Status</div>

                <div class="w-32 text-center">Start Time</div>

                <div class="w-10 text-center"> </div>

                <div class="w-32 text-center">End Time</div>

                <div class="w-24 text-blue-600 text-center">Total Hours</div>

                <div class="w-28 text-center">Lunch Required</div>

                

            </div>

            @foreach ($days as $day)

            @php
            $record = $hours[$day] ?? null;

            $closed = old(strtolower($day).'_closed', $record->is_closed ?? false);
            $start = old(strtolower($day).'_start', $record && !$record->is_closed ? \Carbon\Carbon::parse($record->start_time)->format('g:i A') : '');
            $end = old(strtolower($day).'_end', $record && !$record->is_closed ? \Carbon\Carbon::parse($record->end_time)->format('g:i A') : '');
            @endphp


            <div class="flex flex-wrap md:flex-nowrap items-center gap-4 ">

                <!-- Day Name -->
                <div class="text-sm text-gray-800 w-20">{{ $day }}</div>

                <!-- Closed Checkbox -->
                <label class="flex items-center space-x-2 w-24">
                    <input type="checkbox"
                        class="w-4 h-4"
                        name="{{ strtolower($day) }}_closed"
                        {{ $closed ? 'checked' : '' }}>

                    <span class="text-sm text-gray-800">Closed</span>
                </label>

                <!-- Start Time -->
                <div class="relative ">
                    <input name="{{ strtolower($day) }}_start" class="timepicker w-full md:w-32 border rounded-md px-3 py-2 text-sm shadow-sm pr-10 focus:outline-none focus:ring-2 bg-white text-gray-700 border-gray-300"
                        autocomplete="off"
                        value="{{ $start }}"
                        data-format="HH:mm" {{ $closed ? 'disabled' : '' }} />
                    <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-800" />
                    </span>
                </div>

                <span class="text-center text-sm  w-10">to</span>

                <!-- End Time -->
                <div class="relative">
                    <input name="{{ strtolower($day) }}_end" value="{{ $end }}" class="timepicker w-full md:w-32 border rounded-md px-3 py-2 text-sm shadow-sm pr-10 focus:outline-none focus:ring-2 bg-white text-gray-700 border-gray-300"
                        autocomplete="off"
                        data-format="HH:mm" {{ $closed ? 'disabled' : '' }} />
                    <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-gray-500">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-800" />
                    </span>
                </div>

              
                <!-- Total Hours -->
                <div class="w-24 text-center text-sm text-blue-600 font-semibold">
                    <span id="total-{{ $day }}">—</span>
                </div>

           @php
$lunch = old(strtolower($day).'_lunch', $record->is_lunch_required ?? false);
@endphp

<label class="flex items-center space-x-2 w-28">
    <input type="checkbox"
        name="{{ strtolower($day) }}_lunch"
        class="w-4 h-4"
        {{ $lunch ? 'checked' : '' }}>
    <span class="text-sm">Lunch</span>
</label>

            </div>
            @endforeach
        </div>

        <!-- Buttons -->
        <div class="flex flex-wrap gap-3 mt-6">
            <button type="button" id="copy-weekdays" class="px-4 py-2 text-sm bg-gray-100 border rounded-md hover:bg-gray-200">
                Copy Monday to Weekdays
            </button>

            <button type="button" id="copy-all" class="px-4 py-2 text-sm bg-gray-100 border rounded-md hover:bg-gray-200">
                Copy Monday to All Days
            </button>
        </div>

       <div class="mt-6">
    <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
        Preview (as displayed on website):
    </h4>

    <div class="p-4 bg-gray-50 rounded-md border text-sm">

        <!-- Header -->
        <div class="flex font-semibold text-gray-600 border-b pb-2 mb-2">
            <div class="w-32">Day</div>
            <div class="w-48">Hours</div>
        </div>

        <!-- Rows -->
        @foreach ($days as $day)
        <div id="preview-{{ $day }}" class="flex items-center mb-1">
            <div class="w-32 text-gray-800">{{ $day }}</div>
            <div class="w-48 value text-gray-700">—</div>
        </div>
        @endforeach

    </div>
</div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════════════ --}}
    {{-- Public Website Page — customer-facing presentation of this store's detail page.     --}}
    {{-- Operational data above stays canonical; these fields only shape the public page.    --}}
    @php
        $storePage = isset($store) ? $store->page : null;
    @endphp
    <div class="p-6 bg-white rounded-lg shadow border"
         x-data="{
            imageId:  '{{ old('page_image_media_id', $storePage->image_media_id ?? '') }}',
            imageUrl: '{{ $storePage?->image?->url ?? '' }}',
            ogId:     '{{ old('page_og_image_media_id', $storePage->og_image_media_id ?? '') }}',
            ogUrl:    '{{ $storePage?->ogImage?->url ?? '' }}',
         }">

        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Public Website Page</h3>
            @if(isset($store) && $store->exists)
                <a href="{{ route('front.stores.show', $store->unique_id) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700">
                    <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4"/>
                    View Public Store Page
                </a>
            @endif
        </div>
        <p class="text-xs text-gray-500 -mt-2 mb-4">
            Controls how this store appears on the public website. Name, address, phone, hours, and map
            always come from the operational details above.
        </p>
        <hr class="mb-6 border-gray-300 dark:border-gray-700">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Publish toggle --}}
            <div>
                <label for="page_status" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Display on Website
                </label>
                {!! html()->select('page_status', ['Active' => 'Active', 'Inactive' => 'Inactive'],
                        old('page_status', $storePage->status ?? 'Active'))
                    ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                    ->attributes(['id' => 'page_status']) !!}
                <p class="mt-1 text-xs text-gray-500">Inactive hides the public store page (visitors get a 404).</p>
                @error('page_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Contact strip toggle --}}
            <div>
                <span class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Show Shared Contact Strip</span>
                <label class="inline-flex items-center gap-2 mt-2">
                    <input type="checkbox" name="show_contact_strip" value="1" class="w-4 h-4"
                           {{ old('show_contact_strip', $storePage->show_contact_strip ?? true) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">
                    The strip's content is managed globally (Website Mgmt → Home Page → Contact Strip);
                    this only toggles whether it appears on this store's page.
                </p>
                @error('show_contact_strip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Page heading --}}
            <div>
                <label for="page_heading" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Page Heading
                </label>
                {!! html()->text('page_heading', old('page_heading', $storePage->page_heading ?? null))
                    ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                    ->attributes(['maxlength' => 240, 'placeholder' => 'Defaults to the store name', 'autocomplete' => 'off', 'id' => 'page_heading']) !!}
                @error('page_heading')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Intro text --}}
            <div class="md:col-span-3">
                <label for="intro_text" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Introductory Text
                </label>
                {!! html()->textarea('intro_text', old('intro_text', $storePage->intro_text ?? null))
                    ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                    ->attributes(['rows' => 2, 'maxlength' => 500, 'placeholder' => 'Short welcome line shown under the page heading', 'id' => 'intro_text']) !!}
                @error('intro_text')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div class="md:col-span-3">
                <label for="page_description" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Store Description
                </label>
                {!! html()->textarea('page_description', old('page_description', $storePage->description ?? null))
                    ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                    ->attributes(['rows' => 5, 'maxlength' => 5000, 'placeholder' => 'Longer description shown in the "About" section of the store page', 'id' => 'page_description']) !!}
                @error('page_description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Store image --}}
            <div class="md:col-span-3 border border-gray-200 rounded-lg p-4">
                <span class="block text-sm font-semibold text-gray-700 mb-1">Store Image</span>
                <p class="text-xs text-gray-500 mb-3">
                    Optional location photo shown beside the store introduction. Recommended: landscape,
                    about 1200 × 800 px (3:2). The image keeps its aspect ratio — it is never stretched.
                    Leave empty and the page renders without a placeholder.
                </p>
                <div class="flex items-center gap-5 flex-wrap">
                    <template x-if="imageUrl">
                        <img :src="imageUrl" alt="Store image preview"
                             class="h-24 w-36 object-cover border rounded-md bg-gray-50">
                    </template>
                    <template x-if="!imageUrl">
                        <div class="h-24 w-36 border border-dashed border-gray-300 rounded-md bg-gray-50 flex items-center justify-center text-xs text-gray-400">No image</div>
                    </template>
                    <div class="flex flex-col gap-2">
                        <button type="button"
                                @click="window.MediaPicker.open(m => { imageId = m.id; imageUrl = m.url; })"
                                class="text-sm text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md px-3 py-1.5">
                            Choose from Library / Upload New
                        </button>
                        <button type="button" x-show="imageId" x-cloak
                                @click="imageId = ''; imageUrl = ''"
                                class="text-sm text-red-500 hover:underline text-left">
                            Remove Image
                        </button>
                    </div>
                </div>
                <input type="hidden" name="page_image_media_id" :value="imageId">
                @error('page_image_media_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- SEO / Open Graph --}}
            <div class="md:col-span-3 border border-gray-200 rounded-lg p-4">
                <span class="block text-sm font-semibold text-gray-700 mb-1">SEO &amp; Social Sharing</span>
                <p class="text-xs text-gray-500 mb-4">
                    All fields are optional. Blank fields fall back automatically:
                    SEO title → store name + site name; description → introductory text → generated
                    location summary; OG fields → the SEO values; OG image → store image → site default.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="seo_title" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">SEO Title</label>
                        {!! html()->text('seo_title', old('seo_title', $storePage->seo_title ?? null))
                            ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                            ->attributes(['maxlength' => 240, 'placeholder' => 'Defaults to "Store Name - Site Name"', 'id' => 'seo_title']) !!}
                        @error('seo_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="canonical_url" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Canonical URL</label>
                        {!! html()->text('canonical_url', old('canonical_url', $storePage->canonical_url ?? null))
                            ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                            ->attributes(['maxlength' => 500, 'placeholder' => 'Defaults to this page\'s own URL', 'id' => 'canonical_url']) !!}
                        @error('canonical_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="page_meta_description" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Meta Description</label>
                        {!! html()->textarea('page_meta_description', old('page_meta_description', $storePage->meta_description ?? null))
                            ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                            ->attributes(['rows' => 2, 'maxlength' => 500, 'id' => 'page_meta_description']) !!}
                        @error('page_meta_description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="og_title" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">OG Title</label>
                        {!! html()->text('og_title', old('og_title', $storePage->og_title ?? null))
                            ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                            ->attributes(['maxlength' => 240, 'id' => 'og_title']) !!}
                        @error('og_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="og_description" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">OG Description</label>
                        {!! html()->text('og_description', old('og_description', $storePage->og_description ?? null))
                            ->class('w-full rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-blue-500 dark:bg-gray-900 dark:text-white')
                            ->attributes(['maxlength' => 500, 'id' => 'og_description']) !!}
                        @error('og_description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <span class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">OG Image</span>
                        <p class="text-xs text-gray-500 mb-2">Recommended 1200 × 630 px. Blank falls back to the store image, then the site default.</p>
                        <div class="flex items-center gap-4 flex-wrap">
                            <template x-if="ogUrl">
                                <img :src="ogUrl" alt="OG image preview" class="h-16 w-28 object-cover border rounded-md bg-gray-50">
                            </template>
                            <button type="button"
                                    @click="window.MediaPicker.open(m => { ogId = m.id; ogUrl = m.url; })"
                                    class="text-sm text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md px-3 py-1.5">
                                Choose from Library / Upload New
                            </button>
                            <button type="button" x-show="ogId" x-cloak
                                    @click="ogId = ''; ogUrl = ''"
                                    class="text-sm text-red-500 hover:underline text-left">
                                Remove
                            </button>
                        </div>
                        <input type="hidden" name="page_og_image_media_id" :value="ogId">
                        @error('page_og_image_media_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>



@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.timepicker').forEach(el => {

            let lastValue = el.value ?
                flatpickr.parseDate(el.value, "h:i K") :
                null;

            flatpickr(el, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false,

                onClose: function(selectedDates, dateStr) {
                    const newValue = dateStr ?
                        flatpickr.parseDate(dateStr, "h:i K") :
                        null;

                    const changed = (
                        (lastValue && newValue && newValue.getTime() !== lastValue.getTime()) ||
                        (!lastValue && newValue)
                    );

                    if (changed) {
                        el.value = dateStr;
                        el.dispatchEvent(new Event('input'));
                        lastValue = newValue;
                    }
                }
            });
        });


        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        function updatePreview() {
            days.forEach(day => {
                const closed = document.querySelector(`[name="${day.toLowerCase()}_closed"]`).checked;
                const start = document.querySelector(`[name="${day.toLowerCase()}_start"]`).value;
                const end = document.querySelector(`[name="${day.toLowerCase()}_end"]`).value;

             
                let display = 'Closed';
                let total = '—';

                if (!closed && start && end) {
                    display = formatTime(start) + ' – ' + formatTime(end);
                      total = calculateHours(start, end);
                }

                document.querySelector(`#preview-${day} .value`).textContent = display;

                // total column
        const totalEl = document.getElementById(`total-${day}`);
        if (totalEl) {
            totalEl.textContent = total;
        }
            });
        }


        function calculateHours(start, end) {
    if (!start || !end) return '—';

    const startDate = flatpickr.parseDate(start, "h:i K");
    const endDate = flatpickr.parseDate(end, "h:i K");

    if (!startDate || !endDate) return '—';

    let diff = (endDate - startDate) / 1000 / 60; // minutes

    if (diff < 0) return '—';

    const hours = Math.floor(diff / 60);
    const minutes = diff % 60;

    return `${hours}h ${minutes}m`;
}

        function formatTime(time) {

            // If time already has AM/PM, return as-is (Flatpickr formatted)
            if (/AM|PM/i.test(time)) {
                return time.toUpperCase();
            }

            // Otherwise convert manually
            let [hour, minute] = time.split(':');
            hour = parseInt(hour);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;

            return `${hour}:${minute} ${ampm}`;
        }


        // Closed toggle + input preview updates
        days.forEach(day => {
            const closed = document.querySelector(`[name="${day.toLowerCase()}_closed"]`);
            const start = document.querySelector(`[name="${day.toLowerCase()}_start"]`);
            const end = document.querySelector(`[name="${day.toLowerCase()}_end"]`);
            const lunch = document.querySelector(`[name="${day.toLowerCase()}_lunch"]`);

            //  INITIAL STATE FIX
            if (closed.checked) {
                start.disabled = true;
                end.disabled = true;
                if (lunch) {
                    lunch.disabled = true;
                    lunch.checked = false;
                }
            }

            closed.addEventListener('change', () => {
                start.disabled = closed.checked;
                end.disabled = closed.checked;

                //  ADD THIS
                    const lunch = document.querySelector(`[name="${day.toLowerCase()}_lunch"]`);
                    if (lunch) {
                        lunch.disabled = closed.checked;

                        // optional: uncheck when closed
                        if (closed.checked) {
                            lunch.checked = false;
                        }
                    }


                updatePreview();
            });

            start.addEventListener('input', updatePreview);
            end.addEventListener('input', updatePreview);
        });

        document.querySelector('#copy-weekdays').addEventListener('click', () => {
            copyTimes('Monday', ['Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        });

        document.querySelector('#copy-all').addEventListener('click', () => {
            copyTimes('Monday', days);
        });

        function copyTimes(from, toDays) {
            const closed = document.querySelector(`[name="${from.toLowerCase()}_closed"]`).checked;
            const start = document.querySelector(`[name="${from.toLowerCase()}_start"]`).value;
            const end = document.querySelector(`[name="${from.toLowerCase()}_end"]`).value;
            const lunch = document.querySelector(`[name="${from.toLowerCase()}_lunch"]`).checked;

            toDays.forEach(day => {
                if (day === from) return;

                const closedEl = document.querySelector(`[name="${day.toLowerCase()}_closed"]`);
                const startEl = document.querySelector(`[name="${day.toLowerCase()}_start"]`);
                const endEl = document.querySelector(`[name="${day.toLowerCase()}_end"]`);
                const lunchEl = document.querySelector(`[name="${day.toLowerCase()}_lunch"]`);

                closedEl.checked = closed;
                startEl.value = start;
                endEl.value = end;

                startEl.disabled = closed;
                endEl.disabled = closed;

                if (lunchEl) {
                    lunchEl.checked = closed ? false : lunch;
                    lunchEl.disabled = closed;
                }
            });

            updatePreview();
        }

        updatePreview();
    });
</script>

<script>
    // ── Service Area dynamic rows ────────────────────────────────────────────
    (function () {
        const counters = {
            included: document.querySelectorAll('#sa-included-rows tr.sa-row').length,
            excluded: document.querySelectorAll('#sa-excluded-rows tr.sa-row').length,
        };

        window.addServiceAreaRow = function (group) {
            const template = document.getElementById(`sa-${group}-template`);
            const tbody    = document.getElementById(`sa-${group}-rows`);
            const empty    = document.getElementById(`sa-${group}-empty`);

            const idx      = counters[group]++;
            const clone    = template.querySelector('tr').cloneNode(true);

            clone.innerHTML = clone.innerHTML.replaceAll('__IDX__', idx);
            tbody.appendChild(clone);
            empty.classList.add('hidden');
        };

        window.removeServiceAreaRow = function (btn, group) {
            const row   = btn.closest('tr.sa-row');
            const tbody = document.getElementById(`sa-${group}-rows`);
            row.remove();

            const empty = document.getElementById(`sa-${group}-empty`);
            if (!tbody.querySelector('tr.sa-row')) {
                empty.classList.remove('hidden');
            }
        };
    })();
</script>
@endpush

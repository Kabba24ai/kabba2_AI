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
            <div>
                
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

    <div class="p-6 bg-white rounded-lg shadow border">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
            Hours of Operation
        </h3>

        @php
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        @endphp

        <div class="space-y-4">
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

        <!-- Preview -->
        <div class="mt-6">
            <h4 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
                Preview (as displayed on website):
            </h4>

            <div class="p-4 bg-gray-50 rounded-md border text-sm leading-6">
                @foreach ($days as $day)
                <div id="preview-{{ $day }}" class="flex justify-between mb-1">
                    <span>{{ $day }}</span>
                    <span class="value">—</span>
                </div>
                @endforeach
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

                if (!closed && start && end) {
                    display = formatTime(start) + ' – ' + formatTime(end);
                }

                document.querySelector(`#preview-${day} .value`).textContent = display;
            });
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

            closed.addEventListener('change', () => {
                start.disabled = closed.checked;
                end.disabled = closed.checked;
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

            toDays.forEach(day => {
                if (day === from) return;

                document.querySelector(`[name="${day.toLowerCase()}_closed"]`).checked = closed;
                document.querySelector(`[name="${day.toLowerCase()}_start"]`).value = start;
                document.querySelector(`[name="${day.toLowerCase()}_end"]`).value = end;

                document.querySelector(`[name="${day.toLowerCase()}_start"]`).disabled = closed;
                document.querySelector(`[name="${day.toLowerCase()}_end"]`).disabled = closed;
            });

            updatePreview();
        }

        updatePreview();
    });
</script>
@endpush

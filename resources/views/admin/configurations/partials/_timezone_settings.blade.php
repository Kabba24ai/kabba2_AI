{{-- Time Zone Settings --}}
<div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-2">
        <x-heroicon-o-clock class="h-5 w-5 text-brand-600" />
        <h3 class="text-base font-semibold text-gray-800">Time Zone Settings</h3>
    </div>
    <p class="text-sm text-gray-500 mb-5">
        Controls the timezone used when evaluating and sending CRM Funnel SMS messages through Twilio.
        This setting does <strong>not</strong> affect order scheduling, dispatch times, reporting, or any other area of the system.
    </p>

    <div class="max-w-md">
        <label for="twilio_timezone" class="block text-sm font-medium text-gray-700 mb-1">
            CRM / Twilio SMS Timezone
        </label>
        <select
            name="twilio_timezone"
            id="twilio_timezone"
            class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">

            @php
                $current = $settings['Time Zone Settings']['twilio_timezone']['setting_value'] ?? 'America/Chicago';
                $zones = [
                    'America/New_York'     => 'Eastern Time (America/New_York)',
                    'America/Chicago'      => 'Central Time (America/Chicago)',
                    'America/Denver'       => 'Mountain Time (America/Denver)',
                    'America/Phoenix'      => 'Mountain Time – No DST (America/Phoenix)',
                    'America/Los_Angeles'  => 'Pacific Time (America/Los_Angeles)',
                    'America/Anchorage'    => 'Alaska Time (America/Anchorage)',
                    'Pacific/Honolulu'     => 'Hawaii Time (Pacific/Honolulu)',
                    'UTC'                  => 'UTC',
                ];
            @endphp

            @foreach ($zones as $tz => $label)
                <option value="{{ $tz }}" @selected($current === $tz)>{{ $label }}</option>
            @endforeach
        </select>

        @error('twilio_timezone')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <p class="mt-2 text-xs text-gray-400">
            Current value: <span class="font-mono font-semibold text-gray-600">{{ $current }}</span>
        </p>
    </div>
</div>

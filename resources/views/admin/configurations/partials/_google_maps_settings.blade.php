{{-- Google Maps & Routing — global platform integration (shared by Field
     Service now, Dispatch later). Secret API key follows the same encrypted +
     masked + master-verify-to-edit convention as Payment Integration. --}}
@php
    $gm = fn ($key) => data_get($settings, "Google Maps Settings.$key.setting_value");
    $connStatus   = $gm('google_maps_connection_status') ?: 'not_tested';
    $lastTested   = $gm('google_maps_last_tested_at');
    $lastSuccess  = $gm('google_maps_last_success_at');
    $inputCls = 'w-full pl-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors disabled:bg-gray-50 disabled:text-gray-500';
    $statusBadge = [
        'ok'                => ['Connected', 'bg-green-100 text-green-700 border-green-200'],
        'not_tested'        => ['Not tested', 'bg-gray-100 text-gray-600 border-gray-200'],
        'not_configured'    => ['Not configured', 'bg-amber-100 text-amber-700 border-amber-200'],
        'invalid_credentials' => ['Invalid credentials', 'bg-red-100 text-red-700 border-red-200'],
    ][$connStatus] ?? [ucfirst(str_replace('_', ' ', $connStatus)), 'bg-red-100 text-red-700 border-red-200'];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center space-x-2 mb-1">
        <x-heroicon-o-map class="h-5 w-5 text-emerald-600" />
        <h3 class="text-lg font-bold text-gray-900">Google Maps & Routing</h3>
    </div>
    <p class="text-sm text-gray-500 mb-4">
        One global routing credential shared across the platform. Field Service uses it to compute
        technician travel time and expected arrival; Dispatch will reuse the same integration for
        route planning. Calls run server-side only — the key is never exposed to the browser.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Enabled --}}
        <div>
            <label for="google_maps_enabled" class="block text-sm font-medium text-gray-700 mb-1">Integration Enabled</label>
            <select name="google_maps_enabled" id="google_maps_enabled" class="{{ $inputCls }} pr-10">
                <option value="1" @selected((string) $gm('google_maps_enabled') === '1')>Enabled</option>
                <option value="0" @selected((string) $gm('google_maps_enabled') !== '1')>Disabled</option>
            </select>
        </div>

        {{-- Provider --}}
        <div>
            <label for="google_maps_provider" class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
            <select name="google_maps_provider" id="google_maps_provider" class="{{ $inputCls }} pr-10">
                <option value="google" @selected(($gm('google_maps_provider') ?: 'google') === 'google')>Google Maps</option>
            </select>
        </div>

        {{-- API Key (encrypted, masked, verify-to-edit) --}}
        <div class="sm:col-span-2">
            <label for="google_maps_api_key" class="block text-sm font-medium text-gray-700 mb-1 required">Google Maps API Key</label>
            <div class="relative">
                {!! html()->input('password', 'google_maps_api_key',
                        old('google_maps_api_key', $gm('google_maps_api_key') ? '************' : ''))
                    ->class([$inputCls, 'pr-12'])
                    ->attributes([
                        'autocomplete' => 'off',
                        'placeholder'  => 'Enter Google Maps API key',
                        'id'           => 'google_maps_api_key',
                        'disabled'     => true,
                    ]) !!}
                {{-- Eye toggle --}}
                <button type="button"
                    class="absolute inset-y-0 right-9 px-2 grid place-items-center text-gray-400 hover:text-gray-600"
                    data-toggle="visibility" data-target="google_maps_api_key" aria-label="Show API key">
                    <x-heroicon-o-eye data-eye-off class="w-5 h-5" />
                    <x-heroicon-o-eye-slash data-eye class="w-5 h-5 hidden" />
                </button>
                {{-- Lock (verify to edit) --}}
                <button type="button"
                    class="absolute inset-y-0 right-0 w-9 grid place-items-center text-blue-600/80 hover:text-blue-700"
                    data-open-verify data-field-id="google_maps_api_key" aria-haspopup="dialog"
                    aria-controls="verify-modal" aria-label="Verify to edit">
                    <x-heroicon-o-lock-closed class="w-4 h-4" />
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">Restrict this key in Google Cloud to your server IPs and to the Geocoding + Routes APIs.</p>
        </div>

        {{-- Traffic-aware --}}
        <div>
            <label for="google_maps_traffic_aware" class="block text-sm font-medium text-gray-700 mb-1">Traffic-Aware Routing</label>
            <select name="google_maps_traffic_aware" id="google_maps_traffic_aware" class="{{ $inputCls }} pr-10">
                <option value="1" @selected((string) ($gm('google_maps_traffic_aware') ?? '1') === '1')>On (use live traffic when a future departure is set)</option>
                <option value="0" @selected((string) ($gm('google_maps_traffic_aware') ?? '1') === '0')>Off (static durations)</option>
            </select>
        </div>

        {{-- Units --}}
        <div>
            <label for="google_maps_units" class="block text-sm font-medium text-gray-700 mb-1">Default Units</label>
            <select name="google_maps_units" id="google_maps_units" class="{{ $inputCls }} pr-10">
                <option value="imperial" @selected(($gm('google_maps_units') ?: 'imperial') === 'imperial')>Imperial (miles)</option>
                <option value="metric" @selected(($gm('google_maps_units') ?: 'imperial') === 'metric')>Metric (kilometres)</option>
            </select>
        </div>

        {{-- Timeout --}}
        <div>
            <label for="google_maps_timeout" class="block text-sm font-medium text-gray-700 mb-1">Request Timeout (seconds)</label>
            {!! html()->input('number', 'google_maps_timeout', old('google_maps_timeout', $gm('google_maps_timeout') ?: '15'))
                ->class([$inputCls])->attributes(['id' => 'google_maps_timeout', 'min' => 1, 'max' => 120]) !!}
            @error('google_maps_timeout')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Connection status + Test Connection (net-new diagnostic) --}}
    <div class="mt-5 rounded-md border border-gray-200 bg-gray-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700">Connection Status</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusBadge[1] }}" id="gm-status-badge">{{ $statusBadge[0] }}</span>
            </div>
            <button type="button" id="gm-test-connection"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition">
                <x-heroicon-o-signal class="w-4 h-4" />
                Test Connection
            </button>
        </div>
        <div class="mt-2 text-xs text-gray-500 flex flex-wrap gap-x-6 gap-y-1">
            <span>Last tested: <span id="gm-last-tested">{{ $lastTested ?: '—' }}</span></span>
            <span>Last successful: <span>{{ $lastSuccess ?: '—' }}</span></span>
        </div>
        <p id="gm-test-result" class="mt-2 text-sm hidden"></p>
    </div>

    {{-- Security notice --}}
    <div class="mt-4 rounded-md p-4 bg-amber-50 border border-amber-200 text-amber-900">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-lock-closed class="w-8 h-8 text-amber-600" />
            <div>
                <h4 class="text-sm font-medium text-amber-800">Security Notice</h4>
                <p class="text-sm mt-1 text-amber-700">
                    The API key is encrypted at rest and never sent to the browser. Admin Code verification is
                    required to view or replace it. In Google Cloud, restrict the key to your approved server IP
                    addresses and to the Geocoding API and Routes API only.
                </p>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
(function () {
    const btn = document.getElementById('gm-test-connection');
    if (!btn) return;
    const badge  = document.getElementById('gm-status-badge');
    const result = document.getElementById('gm-test-result');
    const lastTested = document.getElementById('gm-last-tested');
    const url = "{{ route('admin.configurations.google-maps-test-connection') }}";

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.textContent = 'Testing…';
        result.classList.add('hidden');
        try {
            const tokenTag = document.querySelector('meta[name="csrf-token"]');
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tokenTag ? tokenTag.getAttribute('content') : '',
                },
            });
            const data = await res.json();
            result.textContent = data.message || (data.success ? 'Connection successful.' : 'Connection failed.');
            result.className = 'mt-2 text-sm ' + (data.success ? 'text-green-700' : 'text-red-600');
            result.classList.remove('hidden');
            if (data.tested_at && lastTested) lastTested.textContent = data.tested_at;
            if (badge) {
                badge.textContent = data.success ? 'Connected' : (data.status || 'failed').replace(/_/g, ' ');
                badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border '
                    + (data.success ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200');
            }
        } catch (e) {
            result.textContent = 'Test failed — please try again.';
            result.className = 'mt-2 text-sm text-red-600';
            result.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush

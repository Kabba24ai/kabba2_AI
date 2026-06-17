<form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.settings.save') }}">
    @csrf
    <input type="hidden" name="tab" value="automation">
    {{-- preserve non-automation settings --}}
    {{-- prefer_same_driver_for_returns is managed below as a visible toggle --}}
    <input type="hidden" name="route_efficiency_weight" value="{{ $settings->route_efficiency_weight }}">
    <input type="hidden" name="delivery_priority_weight" value="{{ $settings->delivery_priority_weight }}">
    <input type="hidden" name="driver_utilization_weight" value="{{ $settings->driver_utilization_weight }}">
    <input type="hidden" name="early_delivery_max_days" value="{{ $settings->early_delivery_max_days }}">

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl space-y-6">
        <div>
            <h2 class="text-lg font-semibold mb-1">AI Automation</h2>
            <p class="text-sm text-gray-500">
                Configure nightly AI dispatch planning. When enabled, the AI will automatically build a draft dispatch plan each night.
                All manual edits are protected — AI will never overwrite a locked assignment.
            </p>
        </div>

        {{-- Enable AI --}}
        <div class="flex items-start gap-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
            <div class="flex-1">
                <div class="font-medium text-sm text-indigo-800">Enable Nightly AI Dispatch</div>
                <div class="text-xs text-indigo-600 mt-0.5">AI will run automatically each night at the configured time and build a draft dispatch plan.</div>
            </div>
            <div class="shrink-0 mt-0.5">
                <button type="button" role="switch"
                    class="dispatch-ai-toggle relative inline-block w-11 h-6 rounded-full cursor-pointer"
                    style="background-color:{{ $settings->ai_enabled ? '#4f46e5' : '#d1d5db' }};transition:background-color .2s"
                    data-target="chk-ai-enabled"
                    aria-checked="{{ $settings->ai_enabled ? 'true' : 'false' }}">
                    <span class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full shadow-sm"
                        style="transform:translateX({{ $settings->ai_enabled ? '24px' : '4px' }});transition:transform .2s"></span>
                </button>
                <input type="checkbox" id="chk-ai-enabled" name="ai_enabled" value="1" class="sr-only"
                    {{ $settings->ai_enabled ? 'checked' : '' }}>
            </div>
        </div>

        {{-- Cron schedule --}}
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Run Time — Hour (0–23)</label>
                <input type="number" name="cron_hour" min="0" max="23"
                    value="{{ $settings->cron_hour }}"
                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Default: 2 (2:00 AM)</p>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Run Time — Minute (0–59)</label>
                <input type="number" name="cron_minute" min="0" max="59"
                    value="{{ $settings->cron_minute }}"
                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Look Ahead Window (days)</label>
                <select name="look_ahead_days"
                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach ([1, 3, 5, 7, 14] as $days)
                        <option value="{{ $days }}" {{ $settings->look_ahead_days == $days ? 'selected' : '' }}>
                            {{ $days }} {{ $days === 1 ? 'day' : 'days' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Driver continuity --}}
        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex-1">
                <div class="font-medium text-sm text-gray-800">Prefer Same Driver for Returns</div>
                <div class="text-xs text-gray-500 mt-0.5">
                    When enabled, the AI will attempt to assign the same driver for a pickup/return
                    as made the original delivery. A +25 continuity bonus score is applied during driver selection.
                </div>
            </div>
            <div class="shrink-0 mt-0.5">
                <button type="button" role="switch"
                    class="dispatch-ai-toggle relative inline-block w-11 h-6 rounded-full cursor-pointer"
                    style="background-color:{{ $settings->prefer_same_driver_for_returns ? '#4f46e5' : '#d1d5db' }};transition:background-color .2s"
                    data-target="chk-prefer-same-driver"
                    aria-checked="{{ $settings->prefer_same_driver_for_returns ? 'true' : 'false' }}">
                    <span class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full shadow-sm"
                        style="transform:translateX({{ $settings->prefer_same_driver_for_returns ? '24px' : '4px' }});transition:transform .2s"></span>
                </button>
                <input type="checkbox" id="chk-prefer-same-driver" name="prefer_same_driver_for_returns" value="1" class="sr-only"
                    {{ $settings->prefer_same_driver_for_returns ? 'checked' : '' }}>
            </div>
        </div>

        {{-- Allow toggles --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-3">AI Capabilities</h3>
            <div class="space-y-3">
                @php
                    $capabilities = [
                        'auto_build_draft'       => 'Auto-build draft (AI saves the plan automatically)',
                        'allow_driver_assignment'=> 'Allow AI Driver Assignment',
                        'allow_truck_assignment' => 'Allow AI Truck Assignment',
                        'allow_trailer_assignment'=> 'Allow AI Trailer Assignment',
                        'allow_route_optimization'=> 'Allow AI Route Optimization',
                        'allow_early_delivery'   => 'Allow AI Early Delivery Recommendations',
                    ];
                @endphp
                @foreach ($capabilities as $field => $label)
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-200">
                        <input type="checkbox" name="{{ $field }}" value="1"
                            class="rounded border-gray-300 text-indigo-600 w-4 h-4"
                            {{ $settings->$field ? 'checked' : '' }}>
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <strong>Note:</strong> Manual edits to the Dispatch page are always protected.
            If a driver or priority is manually assigned, AI will never overwrite it in future draft runs.
            Admins can remove a lock by clearing the driver assignment.
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium">
                Save Automation Settings
            </button>
        </div>
    </div>
</form>

<script>
(function () {
    document.querySelectorAll('.dispatch-ai-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.dataset.target);
            input.checked = !input.checked;
            var on = input.checked;
            this.style.backgroundColor = on ? '#4f46e5' : '#d1d5db';
            this.querySelector('span').style.transform = on ? 'translateX(24px)' : 'translateX(4px)';
            this.setAttribute('aria-checked', String(on));
        });
    });
})();
</script>

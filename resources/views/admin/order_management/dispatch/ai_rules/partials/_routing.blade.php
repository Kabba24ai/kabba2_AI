<form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.settings.save') }}">
    @csrf
    <input type="hidden" name="tab" value="routing">
    {{-- preserve non-routing settings --}}
    <input type="hidden" name="prefer_same_driver_for_returns" value="{{ $settings->prefer_same_driver_for_returns ? '1' : '0' }}">
    <input type="hidden" name="cron_hour" value="{{ $settings->cron_hour }}">
    <input type="hidden" name="cron_minute" value="{{ $settings->cron_minute }}">
    <input type="hidden" name="look_ahead_days" value="{{ $settings->look_ahead_days }}">
    <input type="hidden" name="early_delivery_max_days" value="{{ $settings->early_delivery_max_days }}">

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-3xl space-y-6">
        <div>
            <h2 class="text-lg font-semibold mb-1">Routing Rules</h2>
            <p class="text-sm text-gray-500">Control how the AI optimizes routes and driver assignments. Weights must sum to 100.</p>
        </div>

        {{-- Weights --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Priority Weights</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Route Efficiency (%)</label>
                    <input type="number" name="route_efficiency_weight" min="0" max="100"
                        value="{{ $settings->route_efficiency_weight }}"
                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Delivery Priority (%)</label>
                    <input type="number" name="delivery_priority_weight" min="0" max="100"
                        value="{{ $settings->delivery_priority_weight }}"
                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Driver Utilization (%)</label>
                    <input type="number" name="driver_utilization_weight" min="0" max="100"
                        value="{{ $settings->driver_utilization_weight }}"
                        class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- Routing Preferences --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Routing Preferences</h3>
            <div class="space-y-3">
                @php
                    $routePrefs = [
                        'route_minimize_miles'          => 'Minimize Total Miles',
                        'route_minimize_drive_time'     => 'Minimize Drive Time',
                        'route_batch_nearby_deliveries' => 'Batch Nearby Deliveries (same driver, same day)',
                        'route_batch_nearby_pickups'    => 'Batch Nearby Returns (same driver, same day)',
                        'route_keep_driver_near_home'   => 'Keep Driver Close to Home Store',
                        'route_respect_delivery_windows'=> 'Respect Customer Delivery Windows',
                    ];
                @endphp
                @foreach ($routePrefs as $field => $label)
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50">
                        <input type="checkbox" name="{{ $field }}" value="1"
                            class="rounded border-gray-300 text-indigo-600 w-4 h-4"
                            {{ $settings->$field ? 'checked' : '' }}>
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium">
                Save Routing Rules
            </button>
        </div>
    </div>
</form>

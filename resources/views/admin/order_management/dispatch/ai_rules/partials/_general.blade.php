<form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.settings.save') }}">
    @csrf
    <input type="hidden" name="tab" value="general">
    {{-- hidden fields to preserve other settings --}}
    <input type="hidden" name="cron_hour" value="{{ $settings->cron_hour }}">
    <input type="hidden" name="cron_minute" value="{{ $settings->cron_minute }}">
    <input type="hidden" name="look_ahead_days" value="{{ $settings->look_ahead_days }}">
    <input type="hidden" name="early_delivery_max_days" value="{{ $settings->early_delivery_max_days }}">
    <input type="hidden" name="route_efficiency_weight" value="{{ $settings->route_efficiency_weight }}">
    <input type="hidden" name="delivery_priority_weight" value="{{ $settings->delivery_priority_weight }}">
    <input type="hidden" name="driver_utilization_weight" value="{{ $settings->driver_utilization_weight }}">

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
        <h2 class="text-lg font-semibold mb-1">General Dispatch Rules</h2>
        <p class="text-sm text-gray-500 mb-6">Global preferences that apply to all AI dispatch planning.</p>

        <div class="space-y-5">

            {{-- Prefer same driver for returns --}}
            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg border">
                <div class="flex-1">
                    <div class="font-medium text-sm">Prefer Same Driver for Returns</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        When enabled, the AI will recommend the same driver that made the delivery to also handle the return pickup for that order.
                        The driver that delivered is almost always best suited for the return.
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                    <input type="checkbox" name="prefer_same_driver_for_returns" value="1" class="sr-only peer"
                        {{ $settings->prefer_same_driver_for_returns ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </div>

        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium">
                Save General Rules
            </button>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.settings.save') }}">
    @csrf
    <input type="hidden" name="tab" value="early_delivery">
    {{-- preserve non-early-delivery settings --}}
    <input type="hidden" name="prefer_same_driver_for_returns" value="{{ $settings->prefer_same_driver_for_returns ? '1' : '0' }}">
    <input type="hidden" name="cron_hour" value="{{ $settings->cron_hour }}">
    <input type="hidden" name="cron_minute" value="{{ $settings->cron_minute }}">
    <input type="hidden" name="look_ahead_days" value="{{ $settings->look_ahead_days }}">
    <input type="hidden" name="route_efficiency_weight" value="{{ $settings->route_efficiency_weight }}">
    <input type="hidden" name="delivery_priority_weight" value="{{ $settings->delivery_priority_weight }}">
    <input type="hidden" name="driver_utilization_weight" value="{{ $settings->driver_utilization_weight }}">

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl space-y-6">
        <div>
            <h2 class="text-lg font-semibold mb-1">Early Delivery Rules</h2>
            <p class="text-sm text-gray-500">
                Rent 'n King frequently delivers early — e.g. weekend rentals delivered Thursday evening instead of Friday.
                Configure how AI should recommend early delivery windows.
            </p>
        </div>

        {{-- Enable early delivery --}}
        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg border">
            <div class="flex-1">
                <div class="font-medium text-sm">Enable AI Early Delivery Recommendations</div>
                <div class="text-xs text-gray-500 mt-0.5">When enabled, AI may suggest delivering earlier than the scheduled date to balance driver workload.</div>
            </div>
            <div class="shrink-0 mt-0.5">
                <button type="button" role="switch"
                    class="dispatch-ai-toggle relative inline-block w-11 h-6 rounded-full cursor-pointer"
                    style="background-color:{{ $settings->early_delivery_enabled ? '#4f46e5' : '#d1d5db' }};transition:background-color .2s"
                    data-target="chk-early-delivery-enabled"
                    aria-checked="{{ $settings->early_delivery_enabled ? 'true' : 'false' }}">
                    <span class="absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm"
                        style="transform:translateX({{ $settings->early_delivery_enabled ? '24px' : '4px' }});transition:transform .2s"></span>
                </button>
                <input type="checkbox" id="chk-early-delivery-enabled" name="early_delivery_enabled" value="1" class="sr-only"
                    {{ $settings->early_delivery_enabled ? 'checked' : '' }}>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Maximum Early Delivery (days)</label>
                <input type="number" name="early_delivery_max_days" min="1" max="7"
                    value="{{ $settings->early_delivery_max_days }}"
                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-gray-400 mt-1">AI will not suggest delivery more than this many days early.</p>
            </div>
            <div></div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Preferred Early Window Start</label>
                <input type="time" name="early_delivery_window_start"
                    value="{{ $settings->early_delivery_window_start }}"
                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Preferred Early Window End</label>
                <input type="time" name="early_delivery_window_end"
                    value="{{ $settings->early_delivery_window_end }}"
                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50">
                <input type="checkbox" name="early_delivery_customer_must_approve" value="1"
                    class="rounded border-gray-300 text-indigo-600 w-4 h-4"
                    {{ $settings->early_delivery_customer_must_approve ? 'checked' : '' }}>
                <div>
                    <div class="text-sm font-medium">Customer Must Approve Early Delivery</div>
                    <div class="text-xs text-gray-400">AI will flag these orders — admin must confirm customer agreement before dispatching early.</div>
                </div>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50">
                <input type="checkbox" name="early_delivery_prioritize_weekend_specials" value="1"
                    class="rounded border-gray-300 text-indigo-600 w-4 h-4"
                    {{ $settings->early_delivery_prioritize_weekend_specials ? 'checked' : '' }}>
                <div>
                    <div class="text-sm font-medium">Prioritize Weekend Specials for Early Delivery</div>
                    <div class="text-xs text-gray-400">Weekend rental orders are the best candidates for Thursday delivery.</div>
                </div>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50">
                <input type="checkbox" name="early_delivery_reduce_friday_load" value="1"
                    class="rounded border-gray-300 text-indigo-600 w-4 h-4"
                    {{ $settings->early_delivery_reduce_friday_load ? 'checked' : '' }}>
                <div>
                    <div class="text-sm font-medium">Reduce Friday Driver Workload</div>
                    <div class="text-xs text-gray-400">AI will try to move eligible Friday deliveries to Thursday to balance end-of-week loads.</div>
                </div>
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium">
                Save Early Delivery Rules
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

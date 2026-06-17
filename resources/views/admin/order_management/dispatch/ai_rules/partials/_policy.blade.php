<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">AI Policy Rules</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                These are the business rules sent to ChatGPT with every dispatch request.
                Edit the <span class="font-medium">Detail</span> text to refine how the AI interprets each rule.
                The <span class="font-medium">Rule</span> line is the core directive and is shown for reference only.
            </p>
        </div>
        <button type="button" id="policy-preview-toggle"
            class="shrink-0 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg">
            <x-heroicon-o-code-bracket class="w-4 h-4" />
            Preview Prompt JSON
        </button>
    </div>

    {{-- JSON Preview --}}
    <div id="policy-preview-panel" class="hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Full Policy JSON sent to ChatGPT</span>
            <span class="text-xs text-gray-400">This reflects your current settings + any saved overrides</span>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-700 p-4 overflow-auto max-h-96">
            <pre class="text-xs text-green-300 whitespace-pre-wrap leading-relaxed font-mono">{{ $policyPreviewJson }}</pre>
        </div>
    </div>

    {{-- Policy Editor Form --}}
    <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.policy.save') }}" id="policy-form">
        @csrf

        @php
            $overrides = $settings->policy_overrides ?? [];

            $ruleLabels = [
                'PRIMARY_PRIORITY_RULE'              => 'Primary Priority Rule',
                'DELIVERY_MAXIMIZATION_RULE'         => 'Delivery Maximization Rule',
                'MISSION_CRITICAL_PICKUP_RULE'       => 'Mission Critical Pickup Rule',
                'CUSTOMER_TO_CUSTOMER_TRANSFER_RULE' => 'Customer-to-Customer Transfer Rule',
                'SHOP_RETURN_RULE'                   => 'Shop Return Rule',
                'OPTIONAL_PICKUP_RULE'               => 'Optional Pickup Rule',
                'DRIVER_CONTINUITY_RULE'             => 'Driver Continuity Rule',
                'EARLY_DELIVERY_RULE'                => 'Early Delivery Rule',
                'ROUTING_OPTIMIZATION_RULES'         => 'Routing Optimization Rules',
                'HARD_CONSTRAINTS'                   => 'Hard Constraints',
            ];

            $constraintLabels = [
                'driver_lock'    => 'Driver Lock Constraint',
                'priority_lock'  => 'Priority Lock Constraint',
                'no_double_book' => 'No Double-Booking Constraint',
                'fabrication'    => 'No Fabrication Constraint',
            ];
        @endphp

        <div class="space-y-4">
        @foreach ($defaultPolicy as $key => $rule)

            @php
                $hasOverride = isset($overrides[$key]);
                $label       = $ruleLabels[$key] ?? $key;
            @endphp

            <div class="bg-white rounded-xl shadow-sm border {{ $hasOverride ? 'border-amber-300' : 'border-gray-200' }} overflow-hidden">

                {{-- Card header --}}
                <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b {{ $hasOverride ? 'border-amber-200' : 'border-gray-200' }}">
                    <span class="font-mono text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded border border-indigo-200">{{ $key }}</span>
                    <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                    @if ($hasOverride)
                        <span class="ml-auto text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">Custom Override</span>
                    @endif
                </div>

                <div class="p-5 space-y-4">

                    {{-- ── HARD_CONSTRAINTS: 4 sub-keys ─────────────────────────────── --}}
                    @if ($key === 'HARD_CONSTRAINTS')
                        <p class="text-xs text-gray-500">These are absolute rules — the AI must never violate them. Edit carefully.</p>
                        @foreach ($constraintLabels as $subKey => $subLabel)
                            @php
                                $defaultVal  = $rule[$subKey] ?? '';
                                $savedVal    = $overrides[$key][$subKey] ?? '';
                                $displayVal  = $savedVal ?: $defaultVal;
                                $isOverridden = $savedVal !== '' && $savedVal !== $defaultVal;
                            @endphp
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="text-xs font-semibold text-gray-700">{{ $subLabel }}</label>
                                    @if ($isOverridden)
                                        <span class="text-xs text-amber-600">overridden</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mb-1">Default: <em>{{ $defaultVal }}</em></div>
                                <textarea name="policy_overrides[{{ $key }}][{{ $subKey }}]" rows="2"
                                    placeholder="Leave blank to use default"
                                    class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ $savedVal }}</textarea>
                            </div>
                        @endforeach

                    {{-- ── ROUTING_OPTIMIZATION_RULES: flags + detail ───────────────── --}}
                    @elseif ($key === 'ROUTING_OPTIMIZATION_RULES')
                        <div class="flex flex-wrap gap-2 mb-1">
                            @foreach (['minimize_miles' => 'Minimize Miles', 'batch_nearby_deliveries' => 'Batch Nearby Deliveries', 'batch_nearby_pickups' => 'Batch Nearby Pickups', 'keep_driver_near_home' => 'Keep Near Home Store'] as $flag => $flagLabel)
                                <span class="text-xs px-2 py-0.5 rounded-full border font-medium
                                    {{ $rule[$flag] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-400 border-gray-200' }}">
                                    {{ $rule[$flag] ? '✓' : '✗' }} {{ $flagLabel }}
                                </span>
                            @endforeach
                            <span class="text-xs text-gray-400 self-center">(flags controlled by Routing tab)</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <label class="text-xs font-semibold text-gray-700">Detail / Instructions</label>
                                @if (!empty($overrides[$key]['detail']))
                                    <span class="text-xs text-amber-600">overridden</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mb-1">Default: <em>{{ $rule['detail'] }}</em></div>
                            <textarea name="policy_overrides[{{ $key }}][detail]" rows="3"
                                placeholder="Leave blank to use default"
                                class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ $overrides[$key]['detail'] ?? '' }}</textarea>
                        </div>

                    {{-- ── Regular rules: rule (read-only) + detail (editable) ──────── --}}
                    @else
                        {{-- Rule text — read-only reference --}}
                        <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-2.5 text-sm text-blue-900 font-medium">
                            {{ $rule['rule'] }}
                        </div>

                        @if (isset($rule['enabled']))
                            <div class="text-xs text-gray-500 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full inline-block {{ $rule['enabled'] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                Currently <strong>{{ $rule['enabled'] ? 'enabled' : 'disabled' }}</strong>
                                — controlled by
                                @if ($key === 'DRIVER_CONTINUITY_RULE') AI Automation tab → "Prefer Same Driver for Returns"
                                @else AI Automation tab → "Allow Early Delivery Recommendations"
                                @endif
                            </div>
                        @endif

                        {{-- Detail — editable --}}
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <label class="text-xs font-semibold text-gray-700">Detail / Instructions</label>
                                @if (!empty($overrides[$key]['detail']))
                                    <span class="text-xs text-amber-600">overridden</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 mb-1 leading-relaxed">Default: <em>{{ $rule['detail'] }}</em></div>
                            <textarea name="policy_overrides[{{ $key }}][detail]" rows="3"
                                placeholder="Leave blank to use the default text above"
                                class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ $overrides[$key]['detail'] ?? '' }}</textarea>
                        </div>
                    @endif

                </div>
            </div>

        @endforeach
        </div>

        {{-- Footer actions --}}
        <div class="flex items-center justify-between pt-2">
            <button type="submit" name="reset_policy" value="1"
                onclick="return confirm('Reset all policy rules to defaults? All custom overrides will be lost.')"
                class="text-sm text-red-600 hover:text-red-800 font-medium">
                Reset All to Defaults
            </button>
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium">
                Save Policy Rules
            </button>
        </div>

    </form>
</div>

<script>
(function () {
    const btn   = document.getElementById('policy-preview-toggle');
    const panel = document.getElementById('policy-preview-panel');
    if (btn && panel) {
        btn.addEventListener('click', function () {
            panel.classList.toggle('hidden');
            this.textContent = panel.classList.contains('hidden') ? 'Preview Prompt JSON' : 'Hide Preview';
        });
    }
})();
</script>

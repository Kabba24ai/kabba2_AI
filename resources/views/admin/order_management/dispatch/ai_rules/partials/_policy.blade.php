<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold">AI Policy Rules</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                These are the business rules sent to ChatGPT with every dispatch request.
                Edit the <strong>Detail</strong> text to refine how the AI interprets each rule.
                Leave a field blank to keep the built-in default.
            </p>
        </div>
        <button type="button" id="policy-preview-toggle"
            class="shrink-0 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg whitespace-nowrap">
            <x-heroicon-o-code-bracket class="w-4 h-4" />
            Preview Prompt JSON
        </button>
    </div>

    {{-- JSON Preview (collapsed by default) --}}
    <div id="policy-preview-panel" class="hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Full Policy JSON — exactly what ChatGPT receives</span>
            <span class="text-xs text-gray-400">Reflects current settings + any saved overrides</span>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-700 p-4 overflow-auto max-h-96">
            <pre class="text-xs text-green-300 whitespace-pre-wrap leading-relaxed font-mono">{{ $policyPreviewJson }}</pre>
        </div>
    </div>

    @php
        $overrides = $settings->policy_overrides ?? [];

        $ruleIcons = [
            'PRIMARY_PRIORITY_RULE'              => 'heroicon-o-arrow-up-circle',
            'DELIVERY_MAXIMIZATION_RULE'         => 'heroicon-o-chart-bar',
            'MISSION_CRITICAL_PICKUP_RULE'       => 'heroicon-o-exclamation-triangle',
            'CUSTOMER_TO_CUSTOMER_TRANSFER_RULE' => 'heroicon-o-arrows-right-left',
            'SHOP_RETURN_RULE'                   => 'heroicon-o-building-storefront',
            'OPTIONAL_PICKUP_RULE'               => 'heroicon-o-calendar',
            'DRIVER_CONTINUITY_RULE'             => 'heroicon-o-user-group',
            'EARLY_DELIVERY_RULE'                => 'heroicon-o-clock',
            'ROUTING_OPTIMIZATION_RULES'         => 'heroicon-o-map',
            'HARD_CONSTRAINTS'                   => 'heroicon-o-shield-check',
        ];

        $ruleLabels = [
            'PRIMARY_PRIORITY_RULE'              => 'Primary Priority',
            'DELIVERY_MAXIMIZATION_RULE'         => 'Delivery Maximization',
            'MISSION_CRITICAL_PICKUP_RULE'       => 'Mission Critical Pickup',
            'CUSTOMER_TO_CUSTOMER_TRANSFER_RULE' => 'Customer-to-Customer Transfer',
            'SHOP_RETURN_RULE'                   => 'Shop Return',
            'OPTIONAL_PICKUP_RULE'               => 'Optional Pickup',
            'DRIVER_CONTINUITY_RULE'             => 'Driver Continuity',
            'EARLY_DELIVERY_RULE'                => 'Early Delivery',
            'ROUTING_OPTIMIZATION_RULES'         => 'Routing Optimization',
            'HARD_CONSTRAINTS'                   => 'Hard Constraints',
        ];

        $constraintLabels = [
            'driver_lock'    => 'Driver Lock',
            'priority_lock'  => 'Priority Lock',
            'no_double_book' => 'No Double-Booking',
            'fabrication'    => 'No Fabrication',
        ];
    @endphp

    <form method="POST" action="{{ route('admin.order-management.dispatch.ai-rules.policy.save') }}">
        @csrf

        <div class="grid grid-cols-3 gap-4">
        @foreach ($defaultPolicy as $key => $rule)
            @php
                $hasOverride = isset($overrides[$key]);
                $label       = $ruleLabels[$key] ?? $key;
                $icon        = $ruleIcons[$key] ?? 'heroicon-o-document-text';
                $spanClass   = $key === 'HARD_CONSTRAINTS' ? 'col-span-3' : '';
            @endphp

            <div class="{{ $spanClass }} bg-white rounded-xl shadow-sm border {{ $hasOverride ? 'border-amber-300' : 'border-gray-200' }} flex flex-col">

                {{-- Card header --}}
                <div class="flex items-center gap-3 px-4 py-3 border-b {{ $hasOverride ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50' }} rounded-t-xl">
                    <x-dynamic-component :component="$icon" class="w-4 h-4 {{ $hasOverride ? 'text-amber-600' : 'text-indigo-600' }} shrink-0" />
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-sm truncate">{{ $label }}</span>
                        <span class="font-mono text-xs text-gray-400 truncate">{{ $key }}</span>
                    </div>
                    @if ($hasOverride)
                        <span class="ml-auto shrink-0 text-xs font-medium text-amber-700 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded-full">Modified</span>
                    @endif
                </div>

                {{-- ── HARD_CONSTRAINTS: 4 sub-fields in 2×2 grid ─────────────── --}}
                @if ($key === 'HARD_CONSTRAINTS')
                    <div class="p-4 grid grid-cols-2 gap-4 flex-1">
                        @foreach ($constraintLabels as $subKey => $subLabel)
                            @php
                                $defaultVal   = $rule[$subKey] ?? '';
                                $savedVal     = $overrides[$key][$subKey] ?? '';
                                $isOverridden = $savedVal !== '' && $savedVal !== $defaultVal;
                            @endphp
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <label class="text-xs font-semibold text-gray-700">{{ $subLabel }}</label>
                                    @if ($isOverridden)
                                        <span class="text-xs text-amber-600 font-medium">• modified</span>
                                    @endif
                                </div>
                                <details class="mb-1">
                                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">View default</summary>
                                    <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ $defaultVal }}</p>
                                </details>
                                <textarea name="policy_overrides[{{ $key }}][{{ $subKey }}]" rows="3"
                                    placeholder="Leave blank to use default"
                                    class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-xs focus:ring-indigo-500 focus:border-indigo-500 flex-1 resize-none">{{ $savedVal }}</textarea>
                            </div>
                        @endforeach
                    </div>

                {{-- ── ROUTING_OPTIMIZATION_RULES ──────────────────────────────── --}}
                @elseif ($key === 'ROUTING_OPTIMIZATION_RULES')
                    <div class="p-4 flex flex-col flex-1 gap-3">
                        {{-- Live flag badges (read-only, driven by Routing tab) --}}
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['minimize_miles' => 'Min Miles', 'batch_nearby_deliveries' => 'Batch Deliveries', 'batch_nearby_pickups' => 'Batch Pickups', 'keep_driver_near_home' => 'Near Home'] as $flag => $flagLabel)
                                <span class="text-xs px-2 py-0.5 rounded-full border font-medium
                                    {{ $rule[$flag] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-400 border-gray-200' }}">
                                    {{ $rule[$flag] ? '✓' : '✗' }} {{ $flagLabel }}
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400">Flags controlled by Routing tab</p>
                        <div class="flex flex-col flex-1">
                            @if (!empty($overrides[$key]['detail']))
                                <div class="flex items-center gap-1 mb-1">
                                    <label class="text-xs font-semibold text-gray-700">Detail / Instructions</label>
                                    <span class="text-xs text-amber-600 font-medium">• modified</span>
                                </div>
                            @else
                                <label class="text-xs font-semibold text-gray-700 mb-1">Detail / Instructions</label>
                            @endif
                            <details class="mb-1">
                                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">View default</summary>
                                <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ $rule['detail'] }}</p>
                            </details>
                            <textarea name="policy_overrides[{{ $key }}][detail]" rows="4"
                                placeholder="Leave blank to use default"
                                class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-xs focus:ring-indigo-500 focus:border-indigo-500 resize-none flex-1">{{ $overrides[$key]['detail'] ?? '' }}</textarea>
                        </div>
                    </div>

                {{-- ── Regular rules ───────────────────────────────────────────── --}}
                @else
                    <div class="p-4 flex flex-col flex-1 gap-3">
                        {{-- Core rule directive --}}
                        <div class="bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 text-xs text-blue-900 font-medium leading-relaxed">
                            {{ $rule['rule'] }}
                        </div>

                        {{-- Enabled indicator for setting-driven rules --}}
                        @if (isset($rule['enabled']))
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $rule['enabled'] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span>
                                    {{ $rule['enabled'] ? 'Enabled' : 'Disabled' }} —
                                    @if ($key === 'DRIVER_CONTINUITY_RULE') controlled by <em>Prefer Same Driver for Returns</em>
                                    @else controlled by <em>Allow Early Delivery</em>
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- Editable detail --}}
                        <div class="flex flex-col flex-1">
                            @if (!empty($overrides[$key]['detail']))
                                <div class="flex items-center gap-1 mb-1">
                                    <label class="text-xs font-semibold text-gray-700">Detail / Instructions</label>
                                    <span class="text-xs text-amber-600 font-medium">• modified</span>
                                </div>
                            @else
                                <label class="text-xs font-semibold text-gray-700 mb-1">Detail / Instructions</label>
                            @endif
                            <details class="mb-1">
                                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">View default</summary>
                                <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ $rule['detail'] }}</p>
                            </details>
                            <textarea name="policy_overrides[{{ $key }}][detail]" rows="4"
                                placeholder="Leave blank to use default"
                                class="w-full border border-gray-300 rounded-md py-1.5 px-3 text-xs focus:ring-indigo-500 focus:border-indigo-500 resize-none flex-1">{{ $overrides[$key]['detail'] ?? '' }}</textarea>
                        </div>
                    </div>
                @endif

            </div>

        @endforeach
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between mt-6">
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
            const hidden = panel.classList.toggle('hidden');
            this.querySelector('span') || (this.lastChild.textContent = hidden ? ' Preview Prompt JSON' : ' Hide Preview');
        });
    }
})();
</script>

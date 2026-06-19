@php $prefix = isset($editing) && $editing ? 'edit' : 'add'; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rule Type <span class="text-red-500">*</span></label>
        <select name="rule_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">Select type…</option>
            @foreach(\App\Enums\Dispatch\DispatchIntelligenceRuleType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
        <select name="source_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            @foreach(\App\Enums\Dispatch\DispatchIntelligenceRuleSource::cases() as $src)
                @if($src->value !== 'ai_generated')
                    <option value="{{ $src->value }}">{{ $src->label() }}</option>
                @endif
            @endforeach
        </select>
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Rule Name <span class="text-red-500">*</span></label>
    <input type="text" name="rule_name" required maxlength="255"
        placeholder="Short descriptive name"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Condition — When does this rule apply? <span class="text-red-500">*</span></label>
    <textarea name="condition" required rows="2"
        placeholder="Describe when/where this rule fires…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Recommendation — What should the AI do? <span class="text-red-500">*</span></label>
    <textarea name="recommendation" required rows="2"
        placeholder="Actionable instruction for the dispatcher or AI…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Reason — Why does this rule exist? <span class="text-red-500">*</span></label>
    <textarea name="reason" required rows="2"
        placeholder="Operational or business rationale…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Priority (1–100)</label>
        <input type="number" name="priority" min="1" max="100" value="50"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confidence (0.00–1.00)</label>
        <input type="number" name="confidence_score" min="0" max="1" step="0.01" value="0.80"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Tags (comma-separated)</label>
    <input type="text" id="{{ $prefix }}-tags-display"
        placeholder="cdl_required, residential, morning_preferred…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
    <div id="{{ $prefix }}-tags-container"></div>
    <p class="text-xs text-gray-400 mt-1">Examples: cdl_required, residential, commercial, long_distance, weather_hold, seasonal</p>
</div>

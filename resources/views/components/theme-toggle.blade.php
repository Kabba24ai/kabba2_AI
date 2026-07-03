@props(['name', 'label', 'value' => '0'])
@php $checked = old($name, $value) === '1' || old($name, $value) === true; @endphp
<div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg border border-gray-100">
    <label for="{{ $name }}" class="text-sm text-gray-700 cursor-pointer select-none">{{ $label }}</label>
    <button type="button"
            role="switch"
            aria-checked="{{ $checked ? 'true' : 'false' }}"
            x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
            x-on:click="on = !on; $el.setAttribute('aria-checked', on)"
            :class="on ? 'bg-blue-600' : 'bg-gray-300'"
            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
        <span :class="on ? 'translate-x-4' : 'translate-x-0.5'"
              class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"></span>
        <input type="hidden" name="{{ $name }}" :value="on ? '1' : '0'">
    </button>
</div>

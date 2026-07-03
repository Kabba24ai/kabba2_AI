@props(['name', 'label', 'value' => '', 'placeholder' => '', 'type' => 'text'])
<div>
    <label for="{{ $name }}" class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $label }}</label>
    <input type="{{ $type }}"
           id="{{ $name }}"
           name="{{ $name }}"
           value="{{ old($name, $value) }}"
           placeholder="{{ $placeholder }}"
           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
</div>

{{--
    Icon Picker Component
    Variables expected:
      $pickerIcons  — array of ['class' => '...', 'label' => '...']
      $currentIcon  — currently selected icon class string (or '')
      $inputName    — form field name (e.g. 'icon')
--}}
<div x-data="{
    pickerOpen: false,
    iconVal: @js($currentIcon),
    rect: null,
    toggle() {
        if (this.pickerOpen) { this.pickerOpen = false; return; }
        this.rect = this.$refs.trigger.getBoundingClientRect();
        this.pickerOpen = true;
    }
}" class="relative">

    <input type="hidden" name="{{ $inputName }}" :value="iconVal">

    <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>

    {{-- Trigger button --}}
    <button type="button"
            x-ref="trigger"
            @click="toggle()"
            class="w-full flex items-center gap-2 border border-gray-300 rounded-md px-3 py-2 bg-white hover:bg-gray-50 text-sm text-left transition-colors">

        {{-- Live icon preview --}}
        <span class="shrink-0 w-5 h-5 flex items-center justify-center text-blue-600">
            @foreach($pickerIcons as $pi)
            <span x-show="iconVal === '{{ $pi['class'] }}'" x-cloak class="flex items-center justify-center">
                <x-dynamic-component :component="$pi['class']" class="w-5 h-5"/>
            </span>
            @endforeach
            <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-300" x-show="!iconVal" x-cloak/>
        </span>

        <span class="flex-1 text-gray-600 truncate text-xs" x-text="iconVal || 'Choose an icon…'"></span>
        <span class="shrink-0 transition-transform duration-200" :class="pickerOpen ? 'rotate-180' : ''">
            <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
        </span>
    </button>

    {{-- Teleported dropdown — appended to <body> so no parent overflow:hidden clips it --}}
    <template x-teleport="body">
        <div x-show="pickerOpen"
             x-cloak
             @click.outside="pickerOpen = false"
             @scroll.window="pickerOpen = false"
             @resize.window="pickerOpen = false"
             @keydown.escape.window="pickerOpen = false"
             :style="rect
                 ? 'position:fixed;top:'+(rect.bottom+4)+'px;left:'+rect.left+'px;width:'+Math.max(rect.width,300)+'px;z-index:99999'
                 : 'display:none'"
             class="bg-white border border-gray-200 rounded-xl shadow-2xl p-3">

            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Select an Icon</p>

            <div class="grid grid-cols-6 gap-1.5 max-h-56 overflow-y-auto pr-0.5">
                @foreach($pickerIcons as $pi)
                @php $pic = $pi['class']; @endphp
                <button type="button"
                        @click="iconVal = '{{ $pic }}'; pickerOpen = false"
                        :class="iconVal === '{{ $pic }}'
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                        class="flex flex-col items-center gap-1 p-2.5 rounded-lg transition-colors"
                        title="{{ $pi['label'] }}">
                    <x-dynamic-component :component="$pic" class="w-6 h-6 shrink-0"/>
                    <span class="text-[10px] leading-tight text-center w-full truncate">{{ $pi['label'] }}</span>
                </button>
                @endforeach
            </div>

            <div class="border-t border-gray-100 mt-2 pt-2 flex justify-end">
                <button type="button"
                        @click="iconVal = ''; pickerOpen = false"
                        class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                    ✕ Clear icon
                </button>
            </div>

        </div>
    </template>

</div>

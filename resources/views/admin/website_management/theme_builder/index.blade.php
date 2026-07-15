@extends('admin.layouts.app')

@section('title', 'Theme Builder')

@php
$tabs = [
    'branding'    => ['icon' => 'heroicon-o-photo',           'label' => 'Branding'],
    'colors'      => ['icon' => 'heroicon-o-swatch',          'label' => 'Colors'],
    'typography'  => ['icon' => 'heroicon-o-document-text',   'label' => 'Typography'],
    'buttons'     => ['icon' => 'heroicon-o-cursor-arrow-rays','label' => 'Buttons'],
    'header'      => ['icon' => 'heroicon-o-bars-3',          'label' => 'Header'],
    'footer'      => ['icon' => 'heroicon-o-rectangle-group', 'label' => 'Footer'],
    'business'    => ['icon' => 'heroicon-o-building-office', 'label' => 'Business'],
    'social'      => ['icon' => 'heroicon-o-share',           'label' => 'Social'],
    'custom_code' => ['icon' => 'heroicon-o-code-bracket',    'label' => 'Custom Code'],
];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Page Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5 flex items-center gap-3">
        <x-heroicon-o-swatch class="w-7 h-7 text-blue-600 shrink-0"/>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Theme Builder</h1>
            <p class="text-sm text-gray-400">Global website settings — colors, fonts, branding, business info, and code.</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('admin.website-management.theme-builder.css-variables') }}"
               target="_blank"
               class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5"/>
                CSS Variables
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm mb-5 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-md px-4 py-3 text-sm mb-5">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex gap-5">

        {{-- ── Left Tab Nav ────────────────────────────────────────────── --}}
        <aside class="w-44 shrink-0">
            <nav class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @foreach($tabs as $key => $tab)
                <a href="{{ route('admin.website-management.theme-builder.show', $key) }}"
                   class="flex items-center gap-2.5 px-4 py-3 text-sm transition-colors border-b border-gray-50 last:border-0
                          {{ $activeTab === $key
                              ? 'bg-blue-50 text-blue-700 font-semibold border-l-2 border-l-blue-500'
                              : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-dynamic-component :component="$tab['icon']" class="w-4 h-4 shrink-0"/>
                    {{ $tab['label'] }}
                </a>
                @endforeach
            </nav>
        </aside>

        {{-- ── Tab Content ─────────────────────────────────────────────── --}}
        <main class="flex-1 min-w-0">

@php
$saveRoute = route('admin.website-management.theme-builder.save', $activeTab);
$th = $theme; // shorthand
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     BRANDING
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'branding')
<form method="POST" action="{{ $saveRoute }}" x-data="themeMediaFields()">
@csrf
<div class="space-y-4">
    {{-- Logos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <x-heroicon-o-photo class="w-4 h-4 text-gray-400"/> Logos & Icons
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach(['logo_primary'=>'Primary Logo','logo_alternate'=>'Alternate Logo','logo_mobile'=>'Mobile Logo','logo_footer'=>'Footer Logo','favicon'=>'Favicon','apple_touch_icon'=>'Apple Touch Icon','og_image_default'=>'Default OG Image'] as $key => $label)
            @php $mediaId = $th[$key] ?? null; $mediaUrl = $mediaId ? optional(\App\Models\Global\Media::find($mediaId))->url : null; @endphp
            <div x-data="{ mediaId: '{{ $mediaId ?? '' }}', imgSrc: '{{ $mediaUrl ?? '' }}' }">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $label }}</label>
                <input type="hidden" name="{{ $key }}" :value="mediaId">
                <div class="h-24 w-full border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center bg-gray-50 overflow-hidden mb-2">
                    <img x-show="imgSrc" :src="imgSrc" class="h-20 w-full object-contain p-1">
                    <span x-show="!imgSrc" class="text-xs text-gray-400">No image</span>
                </div>
                <button type="button"
                        @click="window.MediaPicker.open(m => { mediaId = m.id; imgSrc = m.url; })"
                        class="w-full text-xs text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md px-2 py-1.5 transition-colors">
                    Choose from Library
                </button>
                <button x-show="mediaId" type="button"
                        @click="mediaId = ''; imgSrc = ''"
                        class="w-full mt-1 text-xs text-red-500 hover:underline">
                    Remove
                </button>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Identity --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <x-heroicon-o-building-office class="w-4 h-4 text-gray-400"/> Brand Identity
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-theme-input name="business_name" label="Business Name"        :value="$th['business_name']"/>
            <x-theme-input name="tagline"        label="Tagline / Slogan"     :value="$th['tagline']"/>
        </div>
    </div>

    <x-theme-save-footer/>
</div>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     COLORS
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'colors')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <x-heroicon-o-swatch class="w-4 h-4 text-gray-400"/> Color Palette
    </h2>
    <p class="text-xs text-gray-400 mb-5">These values are exported as CSS custom properties (<code class="bg-gray-100 px-1 rounded">--color-*</code>). Use them in your frontend stylesheet.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($schema['colors'] as $key => $cfg)
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $cfg['label'] }}</label>
            <div class="flex items-center gap-2">
                <input type="color" name="{{ $key }}" value="{{ $th[$key] ?? $cfg['default'] }}"
                       class="h-9 w-14 border border-gray-200 rounded-md cursor-pointer p-0.5 bg-white shrink-0">
                <input type="text" name="{{ $key }}_text"
                       value="{{ $th[$key] ?? $cfg['default'] }}"
                       placeholder="{{ $cfg['default'] }}"
                       oninput="this.previousElementSibling.value = this.value"
                       onchange="document.querySelector('[name={{ $key }}]').value = this.value"
                       class="flex-1 text-xs border border-gray-200 rounded-md px-2 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>
            @if(!empty($cfg['css_var']))
            <p class="text-[10px] text-gray-400 mt-1 font-mono">{{ $cfg['css_var'] }}</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
<x-theme-save-footer/>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     TYPOGRAPHY
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'typography')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <x-heroicon-o-document-text class="w-4 h-4 text-gray-400"/> Typography Settings
    </h2>
    <p class="text-xs text-gray-400 mb-5">Font names are passed as-is to <code class="bg-gray-100 px-1 rounded">font-family</code>. For Google Fonts, add the <code class="bg-gray-100 px-1 rounded">@​import</code> in Custom Code → Header Scripts.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <x-theme-input name="font_heading"   label="Heading Font"       :value="$th['font_heading']"   placeholder="Inter, sans-serif"/>
        <x-theme-input name="font_body"      label="Body Font"          :value="$th['font_body']"      placeholder="Inter, sans-serif"/>
        <x-theme-input name="font_size_base" label="Base Font Size"     :value="$th['font_size_base']" placeholder="16px"/>
        <x-theme-input name="line_height"    label="Line Height"        :value="$th['line_height']"    placeholder="1.6"/>
        <x-theme-input name="letter_spacing" label="Letter Spacing"     :value="$th['letter_spacing']" placeholder="0em"/>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Button Font Weight</label>
            <select name="font_weight_button"
                    class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                @foreach(['400'=>'400 — Normal','500'=>'500 — Medium','600'=>'600 — Semi Bold','700'=>'700 — Bold','800'=>'800 — Extra Bold'] as $w => $wl)
                <option value="{{ $w }}" {{ ($th['font_weight_button'] ?? '600') === $w ? 'selected' : '' }}>{{ $wl }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<x-theme-save-footer/>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     BUTTONS
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'buttons')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <x-heroicon-o-cursor-arrow-rays class="w-4 h-4 text-gray-400"/> Button Styles
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <x-theme-input name="btn_border_radius" label="Border Radius" :value="$th['btn_border_radius']" placeholder="0.375rem"/>
        <x-theme-input name="btn_padding_x"     label="Padding X"     :value="$th['btn_padding_x']"     placeholder="1.25rem"/>
        <x-theme-input name="btn_padding_y"     label="Padding Y"     :value="$th['btn_padding_y']"     placeholder="0.5rem"/>
        <x-theme-input name="btn_hover_opacity" label="Hover Opacity" :value="$th['btn_hover_opacity']" placeholder="0.9"/>
        @foreach(['btn_primary_bg'=>'Primary BG','btn_primary_text'=>'Primary Text Color','btn_secondary_bg'=>'Secondary BG','btn_secondary_text'=>'Secondary Text Color'] as $key => $label)
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $label }}</label>
            <div class="flex items-center gap-2">
                <input type="color" name="{{ $key }}" value="{{ $th[$key] ?: '#ffffff' }}"
                       class="h-9 w-14 border border-gray-200 rounded-md cursor-pointer p-0.5 bg-white shrink-0">
                <input type="text" value="{{ $th[$key] ?? '' }}" placeholder="e.g. #3B82F6"
                       oninput="document.querySelector('[name={{ $key }}]').value = this.value"
                       class="flex-1 text-xs border border-gray-200 rounded-md px-2 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
            </div>
        </div>
        @endforeach
    </div>
</div>
<x-theme-save-footer/>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     HEADER
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'header')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <x-heroicon-o-bars-3 class="w-4 h-4 text-gray-400"/> Header Behaviour
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <x-theme-toggle name="header_sticky"      label="Sticky Header"       :value="$th['header_sticky']"/>
            <x-theme-toggle name="header_transparent" label="Transparent Header"  :value="$th['header_transparent']"/>
            <x-theme-toggle name="header_topbar"      label="Top Bar Enabled"     :value="$th['header_topbar']"/>
            <x-theme-toggle name="header_search"      label="Search Enable"       :value="$th['header_search']"/>
            <x-theme-input  name="header_height"      label="Header Height"       :value="$th['header_height']" placeholder="70px"/>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">CTA Button</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-theme-input name="header_cta_text" label="CTA Button Text" :value="$th['header_cta_text']" placeholder="Get a Quote"/>
            <x-theme-input name="header_cta_url"  label="CTA Button URL"  :value="$th['header_cta_url']"  placeholder="/contact"/>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Announcement Bar</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <x-theme-toggle name="announcement_enabled" label="Announcement Bar Enabled" :value="$th['announcement_enabled']"/>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Announcement Text</label>
                <textarea name="announcement_text" rows="2"
                          class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"
                          placeholder="Free shipping on orders over $100 | Call us: (555) 000-0000">{{ $th['announcement_text'] ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Background Color</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="announcement_bg_color" value="{{ $th['announcement_bg_color'] ?? '#1D4ED8' }}"
                           class="h-9 w-14 border border-gray-200 rounded-md cursor-pointer p-0.5 bg-white">
                    <input type="text" value="{{ $th['announcement_bg_color'] ?? '#1D4ED8' }}"
                           oninput="document.querySelector('[name=announcement_bg_color]').value = this.value"
                           class="flex-1 text-xs border border-gray-200 rounded-md px-2 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Text Color</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="announcement_text_color" value="{{ $th['announcement_text_color'] ?? '#FFFFFF' }}"
                           class="h-9 w-14 border border-gray-200 rounded-md cursor-pointer p-0.5 bg-white">
                    <input type="text" value="{{ $th['announcement_text_color'] ?? '#FFFFFF' }}"
                           oninput="document.querySelector('[name=announcement_text_color]').value = this.value"
                           class="flex-1 text-xs border border-gray-200 rounded-md px-2 py-1.5 font-mono focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
            </div>
        </div>
    </div>
    <x-theme-save-footer/>
</div>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'footer')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <x-heroicon-o-rectangle-group class="w-4 h-4 text-gray-400"/> Footer Settings
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Footer Layout</label>
            <select name="footer_layout"
                    class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                @foreach(['standard'=>'Standard','minimal'=>'Minimal','expanded'=>'Expanded'] as $v => $l)
                <option value="{{ $v }}" {{ ($th['footer_layout'] ?? 'standard') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Copyright Text</label>
            <textarea name="footer_copyright" rows="2"
                      class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"
                      placeholder="© {{ date('Y') }} Company Name. All rights reserved.">{{ $th['footer_copyright'] ?? '' }}</textarea>
        </div>
        <x-theme-toggle name="footer_newsletter"  label="Newsletter Subscribe" :value="$th['footer_newsletter']"/>
        <x-theme-toggle name="footer_social"      label="Social Icons"         :value="$th['footer_social']"/>
        <x-theme-toggle name="footer_back_to_top" label="Back To Top Button"   :value="$th['footer_back_to_top']"/>
    </div>
</div>
<x-theme-save-footer/>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     BUSINESS
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'business')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-1 flex items-center gap-2">
        <x-heroicon-o-building-office class="w-4 h-4 text-gray-400"/> Business Information
    </h2>
    <p class="text-xs text-gray-400 mb-5">These values are available site-wide via <code class="bg-gray-100 px-1 rounded">ThemeService::get('biz_*')</code>.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-theme-input name="biz_name"      label="Business Name"      :value="$th['biz_name']"/>
        <x-theme-input name="biz_phone"     label="Phone Number"       :value="$th['biz_phone']"     placeholder="+1 555 000 0000"/>
        <x-theme-input name="biz_email"     label="Email Address"      :value="$th['biz_email']"     placeholder="hello@company.com"/>
        <x-theme-input name="biz_emergency" label="Emergency Number"   :value="$th['biz_emergency']" placeholder="+1 555 999 9999"/>
        <x-theme-input name="biz_maps_url"  label="Google Maps URL"    :value="$th['biz_maps_url']"  placeholder="https://maps.google.com/..."/>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Address</label>
            <textarea name="biz_address" rows="3"
                      class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"
                      placeholder="123 Main St, City, State, ZIP">{{ $th['biz_address'] ?? '' }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Business Hours</label>
            <textarea name="biz_hours" rows="3"
                      class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"
                      placeholder="Mon–Fri: 8am–6pm&#10;Sat: 9am–2pm&#10;Sun: Closed">{{ $th['biz_hours'] ?? '' }}</textarea>
        </div>
    </div>
</div>
<x-theme-save-footer/>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     SOCIAL
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'social')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-1 flex items-center gap-2">
        <x-heroicon-o-share class="w-4 h-4 text-gray-400"/> Social Networks
    </h2>
    <p class="text-xs text-gray-400 mb-5">Paste the full URL of each profile. Leave empty to hide.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach([
            'social_facebook'  => ['icon'=>'📘', 'placeholder'=>'https://facebook.com/yourpage'],
            'social_instagram' => ['icon'=>'📷', 'placeholder'=>'https://instagram.com/yourhandle'],
            'social_linkedin'  => ['icon'=>'💼', 'placeholder'=>'https://linkedin.com/company/yourcompany'],
            'social_youtube'   => ['icon'=>'▶️', 'placeholder'=>'https://youtube.com/@yourchannel'],
            'social_x'         => ['icon'=>'✖', 'placeholder'=>'https://x.com/yourhandle'],
            'social_tiktok'    => ['icon'=>'🎵', 'placeholder'=>'https://tiktok.com/@yourhandle'],
            'social_pinterest' => ['icon'=>'📌', 'placeholder'=>'https://pinterest.com/yourprofile'],
            'social_threads'   => ['icon'=>'🧵', 'placeholder'=>'https://threads.net/@yourhandle'],
        ] as $key => $info)
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                {{ $info['icon'] }} {{ $schema['social'][$key]['label'] }}
            </label>
            <input type="url" name="{{ $key }}" value="{{ $th[$key] ?? '' }}"
                   placeholder="{{ $info['placeholder'] }}"
                   class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
        </div>
        @endforeach
    </div>
</div>
<x-theme-save-footer/>
</form>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     CUSTOM CODE
═══════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'custom_code')
<form method="POST" action="{{ $saveRoute }}">
@csrf
<div class="space-y-4">
    <div class="bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 text-xs text-amber-800 flex items-start gap-2">
        <x-heroicon-o-exclamation-triangle class="w-4 h-4 mt-0.5 shrink-0"/>
        Custom code is injected into every page. Malformed scripts may break your site. Test thoroughly.
    </div>

    @foreach([
        'custom_css'   => ['label'=>'Custom CSS',          'lang'=>'css',        'hint'=>'Injected inside <style> in <head>.'],
        'head_scripts' => ['label'=>'Header Scripts',      'lang'=>'html',       'hint'=>'Injected before </head>. Use <script> tags.'],
        'footer_scripts'=> ['label'=>'Footer Scripts',     'lang'=>'html',       'hint'=>'Injected before </body>. Use <script> tags.'],
    ] as $key => $info)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <label class="block text-xs font-semibold text-gray-700 mb-1">{{ $info['label'] }}</label>
        <p class="text-xs text-gray-400 mb-2">{{ $info['hint'] }}</p>
        <textarea name="{{ $key }}" rows="6" spellcheck="false"
                  class="w-full text-xs font-mono border border-gray-200 rounded-md px-3 py-2.5 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-y bg-gray-50">{{ $th[$key] ?? '' }}</textarea>
    </div>
    @endforeach

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Tracking & Verification</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-theme-input name="ga_id"             label="Google Analytics ID"   :value="$th['ga_id']"             placeholder="G-XXXXXXXXXX"/>
            <x-theme-input name="gtm_id"            label="Google Tag Manager ID" :value="$th['gtm_id']"            placeholder="GTM-XXXXXXX"/>
            <x-theme-input name="fb_pixel_id"       label="Facebook Pixel ID"     :value="$th['fb_pixel_id']"       placeholder="0000000000000"/>
            <x-theme-input name="meta_verification" label="Meta Verification Tag" :value="$th['meta_verification']" placeholder="&lt;meta name=&quot;facebook-domain-verification&quot; ...&gt;"/>
        </div>
    </div>

    <x-theme-save-footer/>
</div>
</form>
@endif

        </main>
    </div>

    {{-- Frontend Integration Guide --}}
    <details class="mt-5 group">
        <summary class="cursor-pointer text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1.5 select-none">
            <x-heroicon-o-information-circle class="w-3.5 h-3.5"/>
            Frontend integration guide
        </summary>
        <div class="mt-3 bg-gray-50 border border-gray-100 rounded-lg p-4 text-xs text-gray-600 space-y-2">
            <p class="font-semibold text-gray-700">How to wire Theme Builder settings to your frontend:</p>
            <ol class="list-decimal pl-4 space-y-1.5 text-gray-500">
                <li><strong>CSS Variables</strong> — Add to your public layout <code class="bg-white border px-1 rounded">&lt;head&gt;</code>:
                    <br><code class="bg-white border px-1 rounded">&lt;style&gt;{!! app(\App\Services\Website\ThemeService::class)->cssVariables() !!}&lt;/style&gt;</code></li>
                <li><strong>Logos</strong> — Replace hardcoded <code>&lt;img&gt;</code> src with: <code class="bg-white border px-1 rounded">app(ThemeService::class)->imageUrl('logo_primary')</code></li>
                <li><strong>Business info</strong> — <code class="bg-white border px-1 rounded">app(ThemeService::class)->get('biz_phone')</code></li>
                <li><strong>Social links</strong> — <code class="bg-white border px-1 rounded">app(ThemeService::class)->get('social_instagram')</code></li>
                <li><strong>Custom CSS / Scripts</strong> — <code class="bg-white border px-1 rounded">app(ThemeService::class)->get('custom_css')</code> — inject in layout head/body.</li>
                <li><strong>Tracking IDs</strong> — <code class="bg-white border px-1 rounded">app(ThemeService::class)->get('ga_id')</code> — pass to GA initialization script.</li>
                <li><strong>Cache</strong> — All settings are cached for 1 hour. Cache clears automatically on every save.</li>
            </ol>
        </div>
    </details>

</div>
@endsection

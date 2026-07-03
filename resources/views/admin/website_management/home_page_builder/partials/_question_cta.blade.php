{{-- ============================================================
     Admin Partial: Question CTA Section
     Editable: icon, heading, description, button text/url, phone.
============================================================ --}}

@php
    $sectionKey = 'question_cta';
    $content = $section?->content ?? [];
@endphp

<div id="tab-{{ $sectionKey }}" class="tab-content hidden">
    <div class="bg-white border border-gray-100 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 text-gray-400"/> Question CTA Settings
        </h3>
        @if(!$section)
            <p class="text-sm text-gray-400 text-center py-4">Section not initialised yet. Reload the page after saving to activate this tab.</p>
        @else
        <form method="POST" action="{{ route('admin.website-management.pages.section.update', $section->unique_id) }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Heading</label>
                    <input type="text" name="title" value="{{ $section?->title ?? 'Have a question?' }}"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                    <textarea name="subtitle" rows="2"
                              class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"
                              >{{ $section?->subtitle ?? 'Our team is ready to help you find the right equipment for your project.' }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Button Text</label>
                    <input type="text" name="button_text" value="{{ $section?->button_text ?? 'Call Main Sales Line' }}"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Button URL</label>
                    <input type="text" name="button_url" value="{{ $section?->button_url ?? '' }}"
                           placeholder="tel:+16158156734 or /contact"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Phone Number (shown below button)</label>
                    <input type="text" name="content[phone_number]" value="{{ $content['phone_number'] ?? '' }}"
                           placeholder="(615) 815-6734"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Icon (heroicon name)</label>
                    <input type="text" name="content[icon]" value="{{ $content['icon'] ?? 'heroicon-o-chat-bubble-left-ellipsis' }}"
                           placeholder="heroicon-o-chat-bubble-left-ellipsis"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status"
                            class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                        <option value="Active" {{ ($section?->status ?? 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ ($section?->status ?? '') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white text-xs px-4 py-1.5 rounded-md hover:bg-blue-700">Save Settings</button>
            </div>
        </form>
        @endif
    </div>
</div>

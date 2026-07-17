@extends('admin.layouts.app')

@section('title', 'New SMS Broadcast')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">New SMS Broadcast</h2>
            <p class="text-sm text-gray-600 mt-1">Build a broadcast step by step. Nothing is sent until it is confirmed from the Broadcast Queue.</p>
        </div>
        <a href="{{ route('admin.crm.message-management.index') }}"
            class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-5 py-2.5 text-sm rounded-lg hover:bg-gray-50 transition">
            Cancel
        </a>
    </div>

    {{-- Step Indicator --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-6">
        <ol id="wiz-stepper" class="flex flex-wrap items-center gap-x-2 gap-y-2 text-sm">
            @foreach (['Message', 'Audience Type', 'Build Audience', 'Review Recipients', 'Review & Queue'] as $i => $label)
                <li class="flex items-center gap-2" data-step-label="{{ $i + 1 }}">
                    <span class="wiz-step-badge w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border bg-gray-100 text-gray-500 border-gray-200">{{ $i + 1 }}</span>
                    <span class="wiz-step-text font-medium text-gray-500">{{ $label }}</span>
                    @if ($i < 4)
                        <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    {{-- ══════════ STEP 1 — Choose the Message ══════════ --}}
    <div id="wiz-step-1" class="wiz-panel bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">What do you want to send?</h3>
        <p class="text-sm text-gray-500 mb-5">Pick a saved message from the library, or create a new one without leaving the wizard.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
                <select id="wiz-msg-category" class="w-full text-sm px-3 py-2.5 border border-gray-300 rounded-md">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Messages</label>
                <input type="text" id="wiz-msg-search" placeholder="Search by message name..."
                    class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md">
            </div>
        </div>

        <div id="wiz-msg-list" class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-64 overflow-y-auto mb-4">
            {{-- populated by JS --}}
        </div>

        <button type="button" id="wiz-add-message-btn"
            class="inline-flex items-center gap-2 text-sm font-medium text-green-700 hover:text-green-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add New Message
        </button>

        {{-- Preview --}}
        <div id="wiz-msg-preview" class="hidden mt-4 border border-gray-300 rounded-lg p-4 bg-gray-50">
            <div class="flex flex-wrap justify-between text-sm mb-2 gap-2">
                <span><span class="font-medium text-gray-700">Message:</span> <span id="wiz-prev-name" class="text-gray-900"></span></span>
                <span><span class="font-medium text-gray-700">Category:</span> <span id="wiz-prev-category" class="text-gray-900"></span></span>
            </div>
            <div id="wiz-prev-content" class="text-gray-800 text-sm leading-relaxed border border-gray-200 bg-white rounded-md p-3 whitespace-pre-wrap"></div>
            <div class="text-xs text-gray-500 mt-2"><span id="wiz-prev-chars">0</span> characters</div>
        </div>
    </div>

    {{-- ══════════ STEP 2 — Audience Type ══════════ --}}
    <div id="wiz-step-2" class="wiz-panel hidden bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Who should receive this message?</h3>
        <p class="text-sm text-gray-500 mb-5">Choose how the audience is built. Recipients are frozen only when the broadcast is added to the queue.</p>

        <div class="space-y-3">
            <label class="wiz-aud-type flex items-start gap-3 border border-gray-300 rounded-lg p-4 cursor-pointer hover:border-blue-400">
                <input type="radio" name="wiz_audience_type" value="all" class="mt-1 text-blue-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">All eligible CRM recipients</span>
                    <span class="block text-xs text-gray-500 mt-0.5">Every active customer with a valid mobile number — optional exclusion tags.</span>
                </span>
            </label>
            <label class="wiz-aud-type flex items-start gap-3 border border-gray-300 rounded-lg p-4 cursor-pointer hover:border-blue-400">
                <input type="radio" name="wiz_audience_type" value="tags" checked class="mt-1 text-blue-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Customers matching CRM tags</span>
                    <span class="block text-xs text-gray-500 mt-0.5">Match All or Any of the selected tags, minus exclusion tags.</span>
                </span>
            </label>
            <label class="wiz-aud-type flex items-start gap-3 border border-gray-300 rounded-lg p-4 cursor-pointer hover:border-blue-400">
                <input type="radio" name="wiz_audience_type" value="saved" class="mt-1 text-blue-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Saved Audience</span>
                    <span class="block text-xs text-gray-500 mt-0.5">Reuse a saved audience definition — rules resolve against current CRM data.</span>
                </span>
            </label>
        </div>
    </div>

    {{-- ══════════ STEP 3 — Build the Audience ══════════ --}}
    <div id="wiz-step-3" class="wiz-panel hidden bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Build the audience</h3>
        <p class="text-sm text-gray-500 mb-5" id="wiz-step3-sub">Select the tags that define this audience.</p>

        {{-- Saved audience picker --}}
        <div id="wiz-saved-block" class="hidden mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Saved Audience</label>
            <select id="wiz-saved-audience" class="w-full text-sm px-3 py-2.5 border border-gray-300 rounded-md">
                <option value="">Select a saved audience…</option>
                @foreach ($audiences as $audience)
                    <option value="{{ $audience->id }}"
                        data-base-all="{{ $audience->base_all ? 1 : 0 }}"
                        data-mode="{{ $audience->positive_mode }}"
                        data-include="{{ json_encode($audience->include_tag_ids ?? []) }}"
                        data-exclude="{{ json_encode($audience->exclude_tag_ids ?? []) }}">
                        {{ $audience->name }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Rules only — the recipient list resolves fresh from current CRM data.</p>
            <div id="wiz-saved-summary" class="hidden mt-3 text-sm text-gray-700 border border-gray-200 bg-gray-50 rounded-md p-3"></div>
        </div>

        {{-- Positive rule (tags mode) --}}
        <div id="wiz-positive-block" class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Positive matching mode</label>
            <div class="flex items-center gap-6 mb-3">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                    <input type="radio" name="wiz_positive_mode" value="any" checked class="text-blue-600"> Match <b>Any</b> selected tag
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                    <input type="radio" name="wiz_positive_mode" value="all" class="text-blue-600"> Match <b>All</b> selected tags
                </label>
            </div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Include tags <span class="text-red-500">*</span></label>
            <div id="wiz-include-tags" class="border border-gray-300 rounded-md bg-white max-h-40 overflow-y-auto divide-y divide-gray-100">
                @forelse ($tags as $tag)
                    <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-800 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="{{ $tag->id }}" data-name="{{ $tag->name }}" class="wiz-include-tag rounded border-gray-300 text-blue-600">
                        <span>{{ $tag->name }}</span>
                    </label>
                @empty
                    <div class="px-3 py-3 text-sm text-gray-500">No customer tags exist yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Exclusions (all modes) --}}
        <div id="wiz-exclude-block">
            <label class="block text-sm font-medium text-gray-700 mb-1">Exclude tags <span class="text-xs font-normal text-gray-400">(optional — always subtracted)</span></label>
            <div id="wiz-exclude-tags" class="border border-gray-300 rounded-md bg-white max-h-40 overflow-y-auto divide-y divide-gray-100">
                @foreach ($tags as $tag)
                    <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-800 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" value="{{ $tag->id }}" data-name="{{ $tag->name }}" class="wiz-exclude-tag rounded border-gray-300 text-red-500">
                        <span>{{ $tag->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Save as audience --}}
        <div id="wiz-save-audience-block" class="mt-5 border-t border-gray-100 pt-4">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" id="wiz-save-audience-toggle" class="rounded border-gray-300 text-blue-600">
                Save this audience for reuse
            </label>
            <div id="wiz-save-audience-name-wrap" class="hidden mt-2">
                <input type="text" id="wiz-save-audience-name" placeholder="Audience name (e.g. Boom Lift Commercial)"
                    class="w-full md:w-1/2 px-3 py-2.5 text-sm border border-gray-300 rounded-md">
            </div>
        </div>
    </div>

    {{-- ══════════ STEP 4 — Review Recipients ══════════ --}}
    <div id="wiz-step-4" class="wiz-panel hidden bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Is this the correct audience?</h3>
        <p class="text-sm text-gray-500 mb-5">Counts are calculated by the server using the same logic that freezes the final recipient package.</p>

        <div id="wiz-recipient-loading" class="text-sm text-gray-500 py-6 text-center">Calculating recipients…</div>

        <div id="wiz-recipient-review" class="hidden">
            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 text-sm mb-4">
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Audience</span><span id="wiz-rev-audience" class="font-medium text-gray-900 text-right max-w-md"></span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Customers matching positive rules</span><span id="wiz-rev-positive" class="font-semibold text-gray-900"></span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Removed by exclusion tags</span><span id="wiz-rev-excluded" class="font-medium text-amber-700"></span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Missing mobile number</span><span id="wiz-rev-missing" class="font-medium text-gray-700"></span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Invalid phone number</span><span id="wiz-rev-invalid" class="font-medium text-gray-700"></span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Duplicate numbers removed</span><span id="wiz-rev-dupes" class="font-medium text-gray-700"></span></div>
                <div class="flex justify-between px-4 py-3 bg-blue-50"><span class="font-semibold text-blue-900">Final unique eligible recipients</span><span id="wiz-rev-final" class="font-bold text-blue-900 text-lg"></span></div>
            </div>
            <p id="wiz-rev-zero-warning" class="hidden text-sm text-red-600 font-medium">No eligible recipients match this audience. Go back and adjust the audience.</p>
        </div>
    </div>

    {{-- ══════════ STEP 5 — Review the Broadcast ══════════ --}}
    <div id="wiz-step-5" class="wiz-panel hidden bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Is the message and audience correct?</h3>
        <p class="text-sm text-gray-500 mb-5">Adding to the send queue freezes this exact content and recipient list. <b>No SMS messages are sent by this step.</b></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Message</p>
                <p><span class="text-gray-600">Name:</span> <span id="wiz-final-msg-name" class="font-medium text-gray-900"></span></p>
                <p class="mt-1"><span class="text-gray-600">Category:</span> <span id="wiz-final-msg-category" class="font-medium text-gray-900"></span></p>
                <div id="wiz-final-msg-content" class="mt-3 border border-gray-200 bg-gray-50 rounded-md p-3 text-gray-800 whitespace-pre-wrap"></div>
                <p class="text-xs text-gray-500 mt-2"><span id="wiz-final-chars">0</span> characters · ~<span id="wiz-final-segments">1</span> SMS segment(s)</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Audience</p>
                <p id="wiz-final-audience" class="text-gray-900 font-medium"></p>
                <p class="mt-2"><span class="text-gray-600">Final recipients:</span> <span id="wiz-final-count" class="font-bold text-blue-800"></span></p>
                <p class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    Created by {{ auth()->user()->full_name ?? auth()->user()->name ?? '' }}<br>
                    <span id="wiz-final-datetime"></span>
                </p>
            </div>
        </div>
    </div>

    {{-- ══════════ STEP 6 — Broadcast Created ══════════ --}}
    <div id="wiz-step-6" class="wiz-panel hidden bg-white border border-green-200 rounded-xl shadow-sm p-8 text-center">
        <svg class="w-14 h-14 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Broadcast created</h3>
        <p class="text-sm text-gray-600 max-w-lg mx-auto">
            <b>No SMS messages have been sent.</b> The broadcast is in the queue waiting for final confirmation.
            From the queue it can be reviewed, edited, scheduled, sent, cancelled, or deleted according to its status.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
            <a href="{{ route('admin.crm.message-management.broadcast-queue.index') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 text-sm rounded-lg transition">View Broadcast Queue</a>
            <a href="{{ route('admin.crm.message-management.broadcast-wizard.create') }}"
                class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-6 py-3 text-sm rounded-lg hover:bg-gray-50 transition">Create Another Broadcast</a>
            <a href="{{ route('admin.crm.message-management.index') }}"
                class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-6 py-3 text-sm rounded-lg hover:bg-gray-50 transition">Return to Message Library</a>
        </div>
    </div>

    {{-- Footer Nav --}}
    <div id="wiz-footer" class="flex items-center justify-between mt-6">
        <button type="button" id="wiz-back"
            class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-6 py-3 text-sm rounded-lg hover:bg-gray-50 transition invisible">
            ← Back
        </button>
        <button type="button" id="wiz-continue"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 text-sm rounded-lg transition">
            Continue →
        </button>
    </div>

</div>

{{-- ══════════ Add New Message sub-modal ══════════ --}}
<div id="wiz-new-message-modal" style="display:none;"
    class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200 overflow-hidden">
        <div class="flex justify-between items-center px-6 pt-4 pb-3 border-b">
            <h2 class="text-lg font-medium text-gray-900">Add New Message</h2>
            <button type="button" class="text-gray-400 hover:text-gray-700 text-xl" onclick="wizCloseNewMessage()">&times;</button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message Name <span class="text-red-500">*</span></label>
                <input type="text" id="wiz-new-msg-name" placeholder="Enter message title..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <select id="wiz-new-msg-category" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm">
                    <option value="">Select Category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                <textarea id="wiz-new-msg-content" rows="4" placeholder="Write your SMS content here..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t">
            <button type="button" onclick="wizCloseNewMessage()"
                class="px-5 py-2.5 text-sm rounded-lg border border-gray-300 bg-white text-gray-700">Cancel</button>
            <button type="button" id="wiz-new-msg-save"
                class="px-5 py-2.5 text-sm rounded-lg bg-green-600 text-white hover:bg-green-700">Save &amp; Select</button>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────
    var MESSAGES = @json($messages);

    var state = {
        eventId:     @json($event?->id),
        step:        1,
        messageId:   @json($event?->sms_broadcast_id ?? (request('message') ? (int) request('message') : null)),
        audienceType:@json($event?->audience_type ?? 'tags'),
        savedId:     @json($event?->sms_audience_id),
        mode:        @json($event?->positive_mode ?? 'any'),
        include:     @json($event?->include_tag_ids ?? []),
        exclude:     @json($event?->exclude_tag_ids ?? []),
        stats:       null,
    };

    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // ── Stepper rendering ────────────────────────────────────────────
    function renderStepper() {
        document.querySelectorAll('#wiz-stepper [data-step-label]').forEach(function (li) {
            var n = parseInt(li.dataset.stepLabel);
            var badge = li.querySelector('.wiz-step-badge');
            var text  = li.querySelector('.wiz-step-text');
            badge.className = 'wiz-step-badge w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border ';
            text.className  = 'wiz-step-text font-medium ';
            if (n < state.step || state.step === 6) {
                badge.className += 'bg-green-100 text-green-700 border-green-300';
                text.className  += 'text-green-700';
                badge.innerHTML = '✓';
            } else if (n === state.step) {
                badge.className += 'bg-blue-600 text-white border-blue-600';
                text.className  += 'text-blue-700';
                badge.textContent = n;
            } else {
                badge.className += 'bg-gray-100 text-gray-500 border-gray-200';
                text.className  += 'text-gray-500';
                badge.textContent = n;
            }
        });
    }

    function showStep(n) {
        state.step = n;
        for (var i = 1; i <= 6; i++) {
            document.getElementById('wiz-step-' + i).classList.toggle('hidden', i !== n);
        }
        document.getElementById('wiz-back').classList.toggle('invisible', n === 1 || n === 6);
        document.getElementById('wiz-footer').classList.toggle('hidden', n === 6);
        document.getElementById('wiz-continue').textContent =
            n === 5 ? 'Add to Send Queue' : 'Continue →';
        renderStepper();

        if (n === 3) applyStep3Mode();
        if (n === 4) loadRecipientReview();
        if (n === 5) renderFinalReview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Step 1: message list ─────────────────────────────────────────
    function selectedMessage() {
        return MESSAGES.find(function (m) { return m.id === state.messageId; }) || null;
    }

    function renderMessageList() {
        var cat    = document.getElementById('wiz-msg-category').value;
        var term   = document.getElementById('wiz-msg-search').value.toLowerCase();
        var list   = document.getElementById('wiz-msg-list');
        var subset = MESSAGES.filter(function (m) {
            return (!cat || m.category_id === parseInt(cat))
                && (!term || m.name.toLowerCase().includes(term));
        });

        list.innerHTML = subset.length ? '' : '<div class="px-4 py-6 text-sm text-gray-500 text-center">No messages match. Create one with “Add New Message”.</div>';
        subset.forEach(function (m) {
            var row = document.createElement('label');
            row.className = 'flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 ' + (m.id === state.messageId ? 'bg-blue-50' : '');
            row.innerHTML = '<input type="radio" name="wiz_message" class="text-blue-600" value="' + m.id + '" ' + (m.id === state.messageId ? 'checked' : '') + '>' +
                '<span class="flex-1"><span class="block text-sm font-medium text-gray-900">' + m.name + '</span>' +
                '<span class="block text-xs text-gray-500">' + m.category + '</span></span>';
            row.querySelector('input').addEventListener('change', function () {
                state.messageId = m.id;
                renderMessageList();
                renderMessagePreview();
            });
            list.appendChild(row);
        });
        renderMessagePreview();
    }

    function renderMessagePreview() {
        var m = selectedMessage();
        var box = document.getElementById('wiz-msg-preview');
        if (!m) { box.classList.add('hidden'); return; }
        box.classList.remove('hidden');
        document.getElementById('wiz-prev-name').textContent = m.name;
        document.getElementById('wiz-prev-category').textContent = m.category;
        document.getElementById('wiz-prev-content').textContent = m.content;
        document.getElementById('wiz-prev-chars').textContent = m.content.length;
    }

    document.getElementById('wiz-msg-category').addEventListener('change', renderMessageList);
    document.getElementById('wiz-msg-search').addEventListener('input', renderMessageList);

    // ── Add New Message sub-flow ─────────────────────────────────────
    document.getElementById('wiz-add-message-btn').addEventListener('click', function () {
        var modal = document.getElementById('wiz-new-message-modal');
        modal.style.display = 'flex';
        modal.classList.remove('hidden');
    });
    window.wizCloseNewMessage = function () {
        var modal = document.getElementById('wiz-new-message-modal');
        modal.style.display = 'none';
        modal.classList.add('hidden');
    };
    document.getElementById('wiz-new-msg-save').addEventListener('click', function () {
        var name = document.getElementById('wiz-new-msg-name').value.trim();
        var cat  = document.getElementById('wiz-new-msg-category').value;
        var body = document.getElementById('wiz-new-msg-content').value.trim();
        if (!name || !cat || !body) { notyf.error('Name, category, and content are required.'); return; }

        var fd = new FormData();
        fd.append('name', name); fd.append('sms_cat_id', cat); fd.append('description', body);

        fetch("{{ route('admin.crm.message-management.sms-broadcast.store') }}", {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status !== 'success') throw new Error(data.message || 'Could not save message.');
            var catText = document.querySelector('#wiz-new-msg-category option[value="' + cat + '"]').textContent.trim();
            MESSAGES.push({ id: data.broadcast.id, name: name, category_id: parseInt(cat), category: catText, content: body });
            state.messageId = data.broadcast.id;
            notyf.success('Message saved to the library and selected.');
            wizCloseNewMessage();
            document.getElementById('wiz-new-msg-name').value = '';
            document.getElementById('wiz-new-msg-content').value = '';
            renderMessageList();
        })
        .catch(function (e) { notyf.error(e.message); });
    });

    // ── Step 3 behavior per audience type ────────────────────────────
    function audienceType() {
        return document.querySelector('input[name="wiz_audience_type"]:checked').value;
    }

    function applyStep3Mode() {
        var type = audienceType();
        document.getElementById('wiz-saved-block').classList.toggle('hidden', type !== 'saved');
        document.getElementById('wiz-positive-block').classList.toggle('hidden', type !== 'tags');
        document.getElementById('wiz-exclude-block').classList.toggle('hidden', type === 'saved');
        document.getElementById('wiz-save-audience-block').classList.toggle('hidden', type === 'saved');
        document.getElementById('wiz-step3-sub').textContent =
            type === 'all'   ? 'Optionally subtract customers holding any of these exclusion tags.' :
            type === 'saved' ? 'Pick a saved audience — its rules resolve against current CRM data.' :
                               'Choose one positive matching mode, the tags to include, and optional exclusions.';
    }

    document.querySelectorAll('input[name="wiz_audience_type"]').forEach(function (r) {
        r.addEventListener('change', applyStep3Mode);
    });

    document.getElementById('wiz-saved-audience').addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        var box = document.getElementById('wiz-saved-summary');
        if (!opt.value) { box.classList.add('hidden'); return; }
        var baseAll = opt.dataset.baseAll === '1';
        var inc = JSON.parse(opt.dataset.include || '[]');
        var exc = JSON.parse(opt.dataset.exclude || '[]');
        box.classList.remove('hidden');
        box.innerHTML = '<b>' + opt.textContent.trim() + '</b>: ' +
            (baseAll ? 'All eligible CRM recipients' : 'Match ' + (opt.dataset.mode || 'any').toUpperCase() + ' of ' + inc.length + ' tag(s)') +
            (exc.length ? ' — minus ' + exc.length + ' exclusion tag(s)' : '');
    });

    document.getElementById('wiz-save-audience-toggle').addEventListener('change', function () {
        document.getElementById('wiz-save-audience-name-wrap').classList.toggle('hidden', !this.checked);
    });

    function collectAudienceSpec() {
        var type = audienceType();
        if (type === 'saved') {
            var opt = document.getElementById('wiz-saved-audience').selectedOptions[0];
            if (!opt || !opt.value) return null;
            return {
                audience_type: opt.dataset.baseAll === '1' ? 'all' : 'tags',
                sms_audience_id: parseInt(opt.value),
                positive_mode: opt.dataset.mode || 'any',
                include_tags: JSON.parse(opt.dataset.include || '[]'),
                exclude_tags: JSON.parse(opt.dataset.exclude || '[]'),
            };
        }
        return {
            audience_type: type,
            sms_audience_id: null,
            positive_mode: document.querySelector('input[name="wiz_positive_mode"]:checked').value,
            include_tags: Array.from(document.querySelectorAll('.wiz-include-tag:checked')).map(function (c) { return parseInt(c.value); }),
            exclude_tags: Array.from(document.querySelectorAll('.wiz-exclude-tag:checked')).map(function (c) { return parseInt(c.value); }),
        };
    }

    function audienceLabel(spec) {
        if (spec.audience_type === 'all') {
            return 'All eligible CRM recipients' + (spec.exclude_tags.length ? ' — excluding ' + tagNames(spec.exclude_tags).join(', ') : '');
        }
        return 'Match ' + spec.positive_mode.toUpperCase() + ': ' + tagNames(spec.include_tags).join(', ') +
            (spec.exclude_tags.length ? ' — excluding ' + tagNames(spec.exclude_tags).join(', ') : '');
    }

    function tagNames(ids) {
        return ids.map(function (id) {
            var cb = document.querySelector('.wiz-include-tag[value="' + id + '"], .wiz-exclude-tag[value="' + id + '"]');
            return cb ? cb.dataset.name : ('#' + id);
        });
    }

    // ── Draft persistence ────────────────────────────────────────────
    function saveDraft(payload) {
        payload.event_id = state.eventId;
        payload.wizard_step = state.step;
        return fetch("{{ route('admin.crm.message-management.broadcast-wizard.draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload),
        })
        .then(function (r) { return r.json().then(function (d) { if (!r.ok || !d.success) throw new Error(d.message || 'Could not save progress.'); return d; }); })
        .then(function (d) { state.eventId = d.event_id; return d; });
    }

    // ── Step 4: server-side recipient review ─────────────────────────
    function loadRecipientReview() {
        var spec = state.lastSpec;
        document.getElementById('wiz-recipient-loading').classList.remove('hidden');
        document.getElementById('wiz-recipient-review').classList.add('hidden');

        var params = new URLSearchParams();
        params.append('audience_type', spec.audience_type);
        params.append('positive_mode', spec.positive_mode || 'any');
        spec.include_tags.forEach(function (id) { params.append('include_tags[]', id); });
        spec.exclude_tags.forEach(function (id) { params.append('exclude_tags[]', id); });

        fetch("{{ route('admin.crm.message-management.broadcast-wizard.preview-audience') }}?" + params.toString(), {
            headers: { 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            state.stats = data.stats;
            document.getElementById('wiz-rev-audience').textContent = audienceLabel(spec);
            document.getElementById('wiz-rev-positive').textContent = data.stats.positive_count;
            document.getElementById('wiz-rev-excluded').textContent = '− ' + data.stats.excluded_by_tags;
            document.getElementById('wiz-rev-missing').textContent  = '− ' + data.stats.missing_phone;
            document.getElementById('wiz-rev-invalid').textContent  = '− ' + data.stats.invalid_phone;
            document.getElementById('wiz-rev-dupes').textContent    = '− ' + data.stats.duplicates_removed;
            document.getElementById('wiz-rev-final').textContent    = data.stats.final_count;
            document.getElementById('wiz-rev-zero-warning').classList.toggle('hidden', data.stats.final_count > 0);
            document.getElementById('wiz-recipient-loading').classList.add('hidden');
            document.getElementById('wiz-recipient-review').classList.remove('hidden');
        })
        .catch(function () { notyf.error('Could not calculate recipients.'); });
    }

    // ── Step 5: final review ─────────────────────────────────────────
    function renderFinalReview() {
        var m = selectedMessage();
        document.getElementById('wiz-final-msg-name').textContent = m.name;
        document.getElementById('wiz-final-msg-category').textContent = m.category;
        document.getElementById('wiz-final-msg-content').textContent = m.content;
        document.getElementById('wiz-final-chars').textContent = m.content.length;
        document.getElementById('wiz-final-segments').textContent = Math.max(1, Math.ceil(m.content.length / 160));
        document.getElementById('wiz-final-audience').textContent = audienceLabel(state.lastSpec);
        document.getElementById('wiz-final-count').textContent = state.stats ? state.stats.final_count : '—';
        document.getElementById('wiz-final-datetime').textContent = new Date().toLocaleString();
    }

    // ── Navigation ───────────────────────────────────────────────────
    document.getElementById('wiz-back').addEventListener('click', function () {
        if (state.step > 1) showStep(state.step - 1);
    });

    document.getElementById('wiz-continue').addEventListener('click', function () {
        var btn = this;

        if (state.step === 1) {
            if (!state.messageId) { notyf.error('Select a message or create a new one.'); return; }
            btn.disabled = true;
            saveDraft({ sms_broadcast_id: state.messageId })
                .then(function () { showStep(2); })
                .catch(function (e) { notyf.error(e.message); })
                .finally(function () { btn.disabled = false; });
            return;
        }

        if (state.step === 2) { showStep(3); return; }

        if (state.step === 3) {
            var spec = collectAudienceSpec();
            if (!spec) { notyf.error('Select a saved audience.'); return; }
            if (spec.audience_type === 'tags' && spec.include_tags.length === 0) {
                notyf.error('Select at least one include tag.'); return;
            }
            state.lastSpec = spec;
            btn.disabled = true;

            var maybeSaveAudience = Promise.resolve();
            var saveToggle = document.getElementById('wiz-save-audience-toggle');
            if (audienceType() !== 'saved' && saveToggle.checked) {
                var audName = document.getElementById('wiz-save-audience-name').value.trim();
                if (!audName) { notyf.error('Name the audience you want to save.'); btn.disabled = false; return; }
                maybeSaveAudience = fetch("{{ route('admin.crm.message-management.audiences.store') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({
                        name: audName,
                        base_all: spec.audience_type === 'all',
                        positive_mode: spec.positive_mode,
                        include_tags: spec.include_tags,
                        exclude_tags: spec.exclude_tags,
                    }),
                }).then(function (r) { return r.json(); }).then(function (d) {
                    if (d.success) { notyf.success('Audience saved for reuse.'); saveToggle.checked = false; document.getElementById('wiz-save-audience-name-wrap').classList.add('hidden'); }
                });
            }

            maybeSaveAudience
                .then(function () { return saveDraft(spec); })
                .then(function () { showStep(4); })
                .catch(function (e) { notyf.error(e.message); })
                .finally(function () { btn.disabled = false; });
            return;
        }

        if (state.step === 4) {
            if (!state.stats || state.stats.final_count === 0) {
                notyf.error('No eligible recipients — adjust the audience first.'); return;
            }
            showStep(5);
            return;
        }

        if (state.step === 5) {
            btn.disabled = true;
            btn.textContent = 'Adding to queue…';
            fetch("{{ route('admin.crm.message-management.broadcast-wizard.queue', ':id') }}".replace(':id', state.eventId), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            })
            .then(function (r) { return r.json().then(function (d) { if (!r.ok || !d.success) throw new Error(d.message || 'Could not queue broadcast.'); return d; }); })
            .then(function () { showStep(6); })
            .catch(function (e) { notyf.error(e.message); })
            .finally(function () { btn.disabled = false; btn.textContent = 'Add to Send Queue'; });
        }
    });

    // ── Init (fresh, continued draft, or ?message= preselect) ────────
    renderMessageList();
    if (state.audienceType === 'all') {
        document.querySelector('input[name="wiz_audience_type"][value="all"]').checked = true;
    } else if (state.savedId) {
        document.querySelector('input[name="wiz_audience_type"][value="saved"]').checked = true;
        var savedSel = document.getElementById('wiz-saved-audience');
        savedSel.value = String(state.savedId);
        savedSel.dispatchEvent(new Event('change'));
    }
    if (state.mode === 'all') document.querySelector('input[name="wiz_positive_mode"][value="all"]').checked = true;
    (state.include || []).forEach(function (id) {
        var cb = document.querySelector('.wiz-include-tag[value="' + id + '"]'); if (cb) cb.checked = true;
    });
    (state.exclude || []).forEach(function (id) {
        var cb = document.querySelector('.wiz-exclude-tag[value="' + id + '"]'); if (cb) cb.checked = true;
    });

    showStep(1);
}());
</script>
@endpush

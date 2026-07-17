@extends('admin.layouts.app')

@section('title', 'Send Broadcast — ' . $event->name)

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Final Send Authorization</h2>
            <p class="text-sm text-gray-600 mt-1">Nothing is transmitted until the confirmation on the last step.</p>
        </div>
        <a href="{{ route('admin.crm.message-management.broadcast-queue.index') }}"
            class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-5 py-2.5 text-sm rounded-lg hover:bg-gray-50 transition">
            ← Return to Queue
        </a>
    </div>

    {{-- Step Indicator --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-6">
        <ol id="fs-stepper" class="flex items-center gap-2 text-sm">
            @foreach (['Review Broadcast', 'Delivery Timing', 'Final Confirmation'] as $i => $label)
                <li class="flex items-center gap-2" data-step-label="{{ $i + 1 }}">
                    <span class="fs-step-badge w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border bg-gray-100 text-gray-500 border-gray-200">{{ $i + 1 }}</span>
                    <span class="fs-step-text font-medium text-gray-500">{{ $label }}</span>
                    @if ($i < 2)
                        <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <form id="fs-form" method="POST" action="{{ route('admin.crm.message-management.broadcast-queue.authorize', $event->id) }}">
        @csrf

        {{-- ══ Step 1 — Review ══ --}}
        <div id="fs-step-1" class="fs-panel bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Review the frozen broadcast</h3>

            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 text-sm mb-4">
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Source message</span><span class="font-medium text-gray-900">{{ $event->message_name }}</span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Category</span><span class="font-medium text-gray-900">{{ $event->category_name ?? '—' }}</span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Audience</span><span class="font-medium text-gray-900 text-right max-w-sm">{{ $event->audienceSummary() }}</span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Frozen recipients</span><span class="font-bold text-blue-800">{{ $event->recipient_count }}</span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Created by</span><span class="font-medium text-gray-900">{{ $event->createdBy?->full_name ?? '—' }} · {{ $event->created_at->format('M j, Y g:i A') }}</span></div>
                <div class="flex justify-between px-4 py-2.5"><span class="text-gray-600">Current status</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $event->status->badgeClass() }}">{{ $event->status->label() }}</span>
                </div>
            </div>

            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Exact frozen content</p>
            <div class="border border-gray-200 bg-gray-50 rounded-md p-4 text-sm text-gray-800 whitespace-pre-wrap">{{ $event->message_content }}</div>
        </div>

        {{-- ══ Step 2 — Timing ══ --}}
        <div id="fs-step-2" class="fs-panel hidden bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">When should it be delivered?</h3>

            <div class="space-y-3 mb-5">
                <label class="flex items-start gap-3 border border-gray-300 rounded-lg p-4 cursor-pointer hover:border-blue-400">
                    <input type="radio" name="timing" value="now" checked class="mt-1 text-blue-600">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Send Immediately</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Transmission begins as soon as you confirm.</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 border border-gray-300 rounded-lg p-4 cursor-pointer hover:border-blue-400">
                    <input type="radio" name="timing" value="scheduled" {{ $event->scheduled_at ? 'checked' : '' }} class="mt-1 text-blue-600">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Schedule for Later</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Central time — the broadcast stays editable until sending begins.</span>
                    </span>
                </label>
            </div>

            <div id="fs-schedule-wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date &amp; Time <span class="text-red-500">*</span></label>
                <div class="relative md:w-1/2">
                    <input type="text" id="fs-scheduled-at" name="scheduled_at" readonly placeholder="Select date & time"
                        value="{{ $event->scheduled_at?->format('Y-m-d H:i:S') }}"
                        class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm cursor-pointer bg-white">
                    <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ══ Step 3 — Confirmation ══ --}}
        <div id="fs-step-3" class="fs-panel hidden bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Final confirmation</h3>

            <div class="border border-amber-200 bg-amber-50 rounded-lg p-5 mb-5">
                <p id="fs-statement" class="text-sm font-medium text-amber-900"></p>
            </div>

            <p class="text-xs text-gray-500">This is the only point where sending is authorized. The frozen package of {{ $event->recipient_count }} recipients will be transmitted exactly as reviewed.</p>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between mt-6">
            <button type="button" id="fs-back"
                class="inline-flex items-center gap-2 border border-gray-300 bg-white text-gray-700 font-medium px-6 py-3 text-sm rounded-lg hover:bg-gray-50 transition invisible">
                ← Back
            </button>
            <button type="button" id="fs-continue"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 text-sm rounded-lg transition">
                Continue →
            </button>
        </div>
    </form>

</div>
@endsection

@push('js')
<script>
(function () {
    'use strict';

    var step = 1;
    var RECIPIENTS = {{ (int) $event->recipient_count }};
    var _fp = null;

    function renderStepper() {
        document.querySelectorAll('#fs-stepper [data-step-label]').forEach(function (li) {
            var n = parseInt(li.dataset.stepLabel);
            var badge = li.querySelector('.fs-step-badge');
            var text  = li.querySelector('.fs-step-text');
            badge.className = 'fs-step-badge w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border ';
            text.className  = 'fs-step-text font-medium ';
            if (n < step)      { badge.className += 'bg-green-100 text-green-700 border-green-300'; text.className += 'text-green-700'; badge.innerHTML = '✓'; }
            else if (n === step){ badge.className += 'bg-blue-600 text-white border-blue-600'; text.className += 'text-blue-700'; badge.textContent = n; }
            else               { badge.className += 'bg-gray-100 text-gray-500 border-gray-200'; text.className += 'text-gray-500'; badge.textContent = n; }
        });
    }

    function timing() { return document.querySelector('input[name="timing"]:checked').value; }

    function showStep(n) {
        step = n;
        for (var i = 1; i <= 3; i++) document.getElementById('fs-step-' + i).classList.toggle('hidden', i !== n);
        document.getElementById('fs-back').classList.toggle('invisible', n === 1);
        var btn = document.getElementById('fs-continue');
        if (n === 3) {
            var isNow = timing() === 'now';
            btn.textContent = isNow ? 'Send Broadcast Now' : 'Schedule Broadcast';
            btn.className = 'inline-flex items-center gap-2 ' + (isNow ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700') + ' text-white font-medium px-8 py-3 text-sm rounded-lg transition';
            var when = document.getElementById('fs-scheduled-at').value;
            var whenText = _fp && _fp.selectedDates[0]
                ? _fp.selectedDates[0].toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
                : when;
            document.getElementById('fs-statement').textContent = isNow
                ? 'Send this SMS broadcast now to ' + RECIPIENTS + ' recipients.'
                : 'Schedule this SMS broadcast for ' + whenText + ' Central for ' + RECIPIENTS + ' recipients.';
        } else {
            btn.textContent = 'Continue →';
            btn.className = 'inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 text-sm rounded-lg transition';
        }
        renderStepper();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function applyTimingVisibility() {
        document.getElementById('fs-schedule-wrap').classList.toggle('hidden', timing() !== 'scheduled');
    }
    document.querySelectorAll('input[name="timing"]').forEach(function (r) {
        r.addEventListener('change', applyTimingVisibility);
    });

    // Flatpickr (lazy CDN pattern used platform-wide)
    function initPicker() {
        if (_fp) return;
        _fp = flatpickr('#fs-scheduled-at', {
            enableTime: true, dateFormat: 'Y-m-d H:i:S', altInput: true,
            altFormat: 'F j, Y h:i K', minDate: 'today', time_24hr: false, disableMobile: true,
            onReady: function (s, d, inst) { inst.calendarContainer.style.zIndex = '200000'; },
        });
    }
    if (window.flatpickr) initPicker();
    else {
        if (!document.getElementById('flatpickr-css')) {
            var link = document.createElement('link');
            link.id = 'flatpickr-css'; link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(link);
        }
        var sc = document.createElement('script');
        sc.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';
        sc.onload = initPicker;
        document.head.appendChild(sc);
    }

    document.getElementById('fs-back').addEventListener('click', function () {
        if (step > 1) showStep(step - 1);
    });

    document.getElementById('fs-continue').addEventListener('click', function () {
        if (step === 1) { showStep(2); return; }
        if (step === 2) {
            if (timing() === 'scheduled' && !document.getElementById('fs-scheduled-at').value) {
                notyf.error('Choose a date and time for the scheduled send.');
                return;
            }
            showStep(3);
            return;
        }
        // Step 3 — the single point of send authorization
        this.disabled = true;
        document.getElementById('fs-form').submit();
    });

    applyTimingVisibility();
    showStep(1);
}());
</script>
@endpush

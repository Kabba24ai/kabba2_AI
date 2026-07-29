@extends('admin.layouts.app')

@section('title', 'Dispatch AI Rules')

@section('content')

    @include('flash::message')

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.order-management.dispatch.index') }}"
               class="text-gray-500 hover:text-gray-700">
                <x-heroicon-o-arrow-left class="w-5 h-5" />
            </a>
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <x-heroicon-o-cpu-chip class="w-6 h-6 text-indigo-600" />
                Dispatch AI Rules
            </h1>
        </div>

        {{-- Run AI Now button --}}
        <button type="button" id="run-ai-draft-btn"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm">
            <x-heroicon-o-bolt class="w-4 h-4" />
            Run AI Dispatch Now
        </button>
    </div>

    @php $activeTab = request('tab', 'drivers'); @endphp

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-1 overflow-x-auto" aria-label="Tabs">
            @php
                $tabs = [
                    'drivers'         => ['label' => 'Drivers',          'icon' => 'heroicon-o-user-group'],
                    'contract_drivers'=> ['label' => 'Contract Drivers', 'icon' => 'heroicon-o-identification'],
                    'trucks'        => ['label' => 'Trucks',           'icon' => 'heroicon-o-truck'],
                    'trailers'      => ['label' => 'Trailers',         'icon' => 'heroicon-o-rectangle-stack'],
                    'equipment'     => ['label' => 'Equipment',        'icon' => 'heroicon-o-wrench-screwdriver'],
                    'routing'       => ['label' => 'Routing',          'icon' => 'heroicon-o-map'],
                    'early_delivery'=> ['label' => 'Early Delivery',   'icon' => 'heroicon-o-clock'],
                    'automation'         => ['label' => 'AI Automation',      'icon' => 'heroicon-o-cpu-chip'],
                    'policy'             => ['label' => 'AI Policy',          'icon' => 'heroicon-o-document-text'],
                    'intelligence_rules' => ['label' => 'Intelligence Rules',  'icon' => 'heroicon-o-light-bulb'],
                ];
            @endphp
            @foreach ($tabs as $key => $tab)
                <a href="{{ route('admin.order-management.dispatch.ai-rules.index', ['tab' => $key]) }}"
                   class="whitespace-nowrap flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors
                          {{ $activeTab === $key
                              ? 'border-indigo-600 text-indigo-600'
                              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-dynamic-component :component="$tab['icon']" class="w-4 h-4" />
                    {{ $tab['label'] }}
                    @if ($key === 'intelligence_rules' && ($intelligenceRuleCount ?? 0) > 0)
                        <span class="ml-1 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full px-1.5 py-0.5">{{ $intelligenceRuleCount }}</span>
                        @if (($intelligenceRulePendingCount ?? 0) > 0)
                            <span class="text-xs font-semibold bg-amber-100 text-amber-700 rounded-full px-1.5 py-0.5">{{ $intelligenceRulePendingCount }} pending</span>
                        @endif
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Tab Content --}}
    @if ($activeTab === 'drivers')
        @include('admin.order_management.dispatch.ai_rules.partials._drivers')
    @elseif ($activeTab === 'contract_drivers')
        @include('admin.order_management.dispatch.ai_rules.partials._contract_drivers')
    @elseif ($activeTab === 'trucks')
        @include('admin.order_management.dispatch.ai_rules.partials._trucks')
    @elseif ($activeTab === 'trailers')
        @include('admin.order_management.dispatch.ai_rules.partials._trailers')
    @elseif ($activeTab === 'equipment')
        @include('admin.order_management.dispatch.ai_rules.partials._equipment')
    @elseif ($activeTab === 'routing')
        @include('admin.order_management.dispatch.ai_rules.partials._routing')
    @elseif ($activeTab === 'early_delivery')
        @include('admin.order_management.dispatch.ai_rules.partials._early_delivery')
    @elseif ($activeTab === 'automation')
        @include('admin.order_management.dispatch.ai_rules.partials._automation')
    @elseif ($activeTab === 'policy')
        @include('admin.order_management.dispatch.ai_rules.partials._policy')
    @elseif ($activeTab === 'intelligence_rules')
        @include('admin.order_management.dispatch.ai_rules.partials._intelligence_rules')
    @endif

    {{-- Run AI Draft Modal --}}
    <div id="ai-draft-modal" class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-sm shadow-lg p-6 text-center">
            <x-heroicon-o-cpu-chip class="w-10 h-10 text-indigo-600 mx-auto mb-3" />
            <h3 class="text-lg font-semibold mb-2">Run AI Dispatch?</h3>
            <p class="text-sm text-gray-500 mb-5">
                AI will build a dispatch draft for the next <strong>{{ $settings->look_ahead_days }}</strong> days.
                Drafts are viewable on the Dispatch page. Manual edits will not be overwritten.
            </p>
            <div class="flex gap-3 justify-center">
                <button type="button" id="ai-draft-cancel"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" id="ai-draft-confirm"
                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                    Run AI Now
                </button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const modal   = document.getElementById('ai-draft-modal');
        const openBtn = document.getElementById('run-ai-draft-btn');
        const cancel  = document.getElementById('ai-draft-cancel');
        const confirm = document.getElementById('ai-draft-confirm');

        openBtn?.addEventListener('click', () => modal.classList.remove('hidden'));
        cancel?.addEventListener('click',  () => modal.classList.add('hidden'));
        modal?.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });

        confirm?.addEventListener('click', function () {
            confirm.disabled    = true;
            confirm.textContent = 'Running…';

            fetch('{{ route("admin.order-management.dispatch.ai-rules.run-draft") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                modal.classList.add('hidden');
                if (data.success) {
                    if (window.notyf) notyf.success(data.message);
                    else alert(data.message);
                } else {
                    if (window.notyf) notyf.error(data.message);
                    else alert(data.message);
                }
            })
            .catch(() => {
                if (window.notyf) notyf.error('Failed to start AI draft. Please try again.');
                else alert('Failed to start AI draft.');
            })
            .finally(() => {
                confirm.disabled    = false;
                confirm.textContent = 'Run AI Now';
            });
        });
    })();
    </script>

@endsection

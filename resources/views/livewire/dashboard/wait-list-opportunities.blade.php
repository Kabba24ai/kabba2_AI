{{--
    Wait List Opportunities (dashboard) — notification + navigation only.
    Counts come exclusively from WaitListStats via the component; the card
    polls on the same 30s cadence as the other operational cards. Detailed
    match evaluation, contact, and dispositions live on the Wait List page.
--}}
<div @if ($canAccess) wire:poll.30s="refreshOpportunities" @endif class="h-full">
    @if ($canAccess)
        @php $hasOpportunities = $contactOpportunities > 0; @endphp
        <div class="h-full flex flex-col bg-white rounded-2xl border {{ $hasOpportunities ? 'border-blue-200 ring-1 ring-blue-100' : 'border-gray-200' }} shadow-sm p-5">

            {{-- Header --}}
            <div class="flex items-start gap-2 mb-4">
                <div class="w-9 h-9 rounded-lg {{ $hasOpportunities ? 'bg-blue-100' : 'bg-gray-100' }} flex items-center justify-center shrink-0">
                    <x-heroicon-o-bell-alert class="w-5 h-5 {{ $hasOpportunities ? 'text-blue-600' : 'text-gray-400' }}" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        Wait List Opportunities
                        @if ($hasOpportunities)
                            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-bold">
                                {{ $contactOpportunities }}
                            </span>
                        @endif
                    </h2>
                    <p class="text-sm text-gray-500">Equipment returned — customers may be ready to contact</p>
                </div>
            </div>

            {{-- Metrics --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="rounded-xl {{ $hasOpportunities ? 'bg-blue-50' : 'bg-gray-50' }} px-3 py-2.5 col-span-1">
                    <p class="text-[10px] font-semibold uppercase tracking-wide {{ $hasOpportunities ? 'text-blue-600' : 'text-gray-400' }}">Customers to Contact</p>
                    <p class="text-2xl font-bold {{ $hasOpportunities ? 'text-blue-700' : 'text-gray-700' }}">{{ $contactOpportunities }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">New Today</p>
                    <p class="text-2xl font-bold text-gray-700">{{ $newToday }}</p>
                </div>
                @if ($awaitingConversion > 0)
                    <div class="rounded-xl bg-emerald-50 px-3 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-600">Awaiting Conversion</p>
                        <p class="text-2xl font-bold text-emerald-700">{{ $awaitingConversion }}</p>
                    </div>
                @else
                    <div class="rounded-xl bg-gray-50 px-3 py-2.5 opacity-60">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Awaiting Conversion</p>
                        <p class="text-2xl font-bold text-gray-500">0</p>
                    </div>
                @endif
            </div>

            {{-- Opportunity preview OR calm zero state --}}
            <div class="grow">
                @if ($hasOpportunities)
                    <div class="space-y-2">
                        @foreach ($preview as $row)
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 border border-gray-100 px-3 py-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                                    <p class="text-sm text-gray-800 truncate">
                                        <span class="font-semibold">{{ $row['customer'] }}</span>
                                        <span class="text-gray-400">·</span>
                                        {{ $row['demand'] }}
                                    </p>
                                </div>
                                <span class="text-xs font-semibold text-blue-600 whitespace-nowrap">
                                    {{ $row['units'] === 1 ? 'Match Ready' : $row['units'] . ' matching units' }}
                                </span>
                            </div>
                        @endforeach
                        @if ($moreCount > 0)
                            <p class="text-xs text-gray-400 px-1">+{{ $moreCount }} more {{ Str::plural('opportunity', $moreCount) }}</p>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4">
                        <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-green-300" />
                        <p class="text-sm font-medium text-gray-500">No customers currently need contact</p>
                        <p class="text-xs text-gray-400 mt-1">Monitoring active wait-list requests for returned equipment.</p>
                    </div>
                @endif
            </div>

            {{-- Single call to action --}}
            <a href="{{ route('admin.wait-list.index') }}"
                class="mt-4 inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition
                {{ $hasOpportunities ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 hover:bg-gray-500' }}">
                Open Wait List <x-heroicon-o-arrow-right class="w-4 h-4" />
            </a>
        </div>
    @endif
</div>

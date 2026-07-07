@extends('admin.layouts.app')

@section('title', 'Equipment Wait List')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
    .wl-ribbon { clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 74%, 0 100%); }
    details.wl-menu > summary { list-style: none; cursor: pointer; }
    details.wl-menu > summary::-webkit-details-marker { display: none; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use Illuminate\Support\Str;

        $ribbonColor = fn (?int $position) => match ($position) {
            1       => 'bg-red-500',
            2       => 'bg-amber-500',
            3       => 'bg-blue-500',
            default => 'bg-gray-400',
        };
        $badgeColor = fn (?int $position) => match ($position) {
            1       => 'border-red-300 text-red-600 bg-red-50',
            2       => 'border-amber-300 text-amber-600 bg-amber-50',
            3       => 'border-blue-300 text-blue-600 bg-blue-50',
            default => 'border-gray-300 text-gray-500 bg-gray-50',
        };
        $thumbUrl = function ($waitList) {
            if ($waitList->request_type === \App\Enums\WaitList\WaitListRequestType::Category) {
                return $waitList->category?->media?->getUrl();
            }

            // Specific equipment: the unit's own photo, else the product image
            // of the first equipment choice on the list
            $equipment = $waitList->items->first()?->equipment;

            return $equipment?->documentImages?->first()?->media?->getUrl()
                ?? $equipment?->assignedProduct?->media?->getUrl();
        };
        $statusOptions = [
            'active'       => 'Active',
            'acknowledged' => 'Acknowledged',
            'converted'    => 'Converted',
            'cancelled'    => 'Cancelled',
        ];
    @endphp

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
                    <x-heroicon-o-bell-alert class="w-6 h-6 text-blue-600" />
                    Equipment Wait List
                </h1>
                <p class="text-sm text-gray-500 mt-1.5">
                    One record = one equipment need. All customer contact and dispositions are manual.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.wait-list.alerts') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border {{ $stats['open_alerts'] ? 'border-red-300 bg-red-50 text-red-700' : 'border-gray-300 bg-white text-gray-600' }} text-sm font-medium hover:bg-red-100 transition">
                    <x-heroicon-o-bell-alert class="w-4 h-4" />
                    Alerts
                    @if ($stats['open_alerts'])
                        <span class="px-1.5 rounded-full bg-red-600 text-white text-xs font-bold">{{ $stats['open_alerts'] }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.wait-list.create') }}"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    New Wait List
                </a>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Waiting Now</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['waiting'] }}</p>
                @if ($stats['oldest'])
                    <p class="text-xs text-gray-400 mt-1">Longest: {{ $stats['oldest']->age_days }} {{ Str::plural('day', $stats['oldest']->age_days) }}</p>
                @endif
            </div>
            <span class="p-2 rounded-full bg-blue-50 text-blue-600"><x-heroicon-o-clock class="w-5 h-5" /></span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Converted (30 Days)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['converted_30'] }}</p>
                @if ($stats['conversion_rate'] !== null)
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['conversion_rate'] }}% conversion rate</p>
                @endif
            </div>
            <span class="p-2 rounded-full bg-green-50 text-green-600"><x-heroicon-o-check-circle class="w-5 h-5" /></span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Top Categories</p>
                @forelse ($topCategories as $row)
                    <p class="text-xs text-gray-600 mt-1">{{ $row->category?->title }} <span class="font-semibold">({{ $row->demand }})</span></p>
                @empty
                    <p class="text-xs text-gray-300 italic mt-1">No category demand</p>
                @endforelse
            </div>
            <span class="p-2 rounded-full bg-purple-50 text-purple-600"><x-heroicon-o-folder class="w-5 h-5" /></span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Top Equipment</p>
                @forelse ($topEquipment as $row)
                    <p class="text-xs text-gray-600 mt-1">{{ $row->equipment_name }} <span class="font-semibold">({{ $row->demand }})</span></p>
                @empty
                    <p class="text-xs text-gray-300 italic mt-1">No specific-equipment demand</p>
                @endforelse
            </div>
            <span class="p-2 rounded-full bg-orange-50 text-orange-600"><x-heroicon-o-truck class="w-5 h-5" /></span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-8">

        {{-- Filter bar --}}
        <form method="GET" class="mb-5">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Status</label>
                    <details class="wl-menu relative">
                        <summary class="inline-flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white text-gray-700 min-w-44">
                            @if ($statusParam === 'all')
                                All Statuses
                            @else
                                <span class="truncate max-w-40">{{ collect($selectedStatuses)->map(fn ($s) => $statusOptions[$s] ?? ucfirst($s))->implode(', ') ?: 'All Statuses' }}</span>
                                @if (count($selectedStatuses))
                                    <span class="px-1.5 rounded-full bg-blue-600 text-white text-[10px] font-bold">{{ count($selectedStatuses) }}</span>
                                @endif
                            @endif
                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-gray-400 ml-auto" />
                        </summary>
                        <div class="absolute z-20 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg p-2 space-y-1">
                            @foreach ($statusOptions as $value => $label)
                                <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-50 text-sm text-gray-700">
                                    <input type="checkbox" name="status[]" value="{{ $value }}"
                                        @checked($statusParam === 'all' || in_array($value, $selectedStatuses, true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </details>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Category</label>
                    <select name="category_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">All Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Equipment</label>
                    <select name="equipment_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">All Equipment</option>
                        @foreach ($filterEquipment as $unit)
                            <option value="{{ $unit->id }}" @selected(request('equipment_id') == $unit->id)>{{ $unit->equipment_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Store Preference</label>
                    <select name="store" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">All Stores</option>
                        <option value="any_store" @selected(request('store') === 'any_store')>Any Store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected(request('store') == $store->id)>{{ $store->store_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Age</label>
                    <select name="age" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">Any Age</option>
                        <option value="0-1" @selected(request('age') === '0-1')>0–1 days</option>
                        <option value="2-7" @selected(request('age') === '2-7')>2–7 days</option>
                        <option value="8+" @selected(request('age') === '8+')>8+ days</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Priority</label>
                    <select name="priority" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">All Priorities</option>
                        @foreach ([1, 2, 3] as $position)
                            <option value="{{ $position }}" @selected(request('priority') == $position)>Position #{{ $position }}</option>
                        @endforeach
                        <option value="none" @selected(request('priority') === 'none')>No override</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 grow">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer, company, phone…"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white w-full min-w-40 max-w-72 ml-auto">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Search</button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 mt-3">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid', 'page' => null]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition {{ $view === 'grid' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                    <x-heroicon-o-squares-2x2 class="w-3.5 h-3.5" /> Grid View
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list', 'page' => null]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition {{ $view === 'list' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                    <x-heroicon-o-list-bullet class="w-3.5 h-3.5" /> List View
                </a>
                <select name="sort" onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-semibold bg-white text-gray-600">
                    <option value="oldest" @selected(request('sort', 'oldest') === 'oldest')>Sort: Oldest First</option>
                    <option value="newest" @selected(request('sort') === 'newest')>Sort: Newest First</option>
                </select>
            </div>
        </form>

        @if ($waitLists->isEmpty())
            <div class="py-12 text-center">
                <x-heroicon-o-bell-alert class="w-10 h-10 text-gray-200 mx-auto mb-3" />
                <p class="text-sm text-gray-400">No wait list records match these filters.</p>
            </div>
        @elseif ($view === 'grid')

            {{-- Grid view --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach ($waitLists as $waitList)
                    @php
                        $position = $queuePositions[$waitList->id] ?? null;
                        $thumb    = $thumbUrl($waitList);
                        $note     = trim((string) Str::of($waitList->internal_notes ?? '')->before("\n"));
                    @endphp
                    <div class="relative bg-white rounded-xl border {{ $position !== null && $position <= 3 ? 'border-gray-300' : 'border-gray-200' }} shadow-sm flex flex-col">
                        @if ($position !== null)
                            <span class="wl-ribbon absolute top-0 left-4 w-7 h-9 {{ $ribbonColor($position) }} text-white text-sm font-bold flex items-start justify-center pt-1">{{ $position }}</span>
                        @endif

                        <div class="p-4 pb-3 grow">
                            <div class="flex items-center justify-end gap-2 mb-2">
                                <span class="text-xs text-gray-400">Waiting {{ $waitList->age_days }}d</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $waitList->status->color() }}">
                                    {{ $waitList->status->label() }}
                                </span>
                            </div>

                            <div class="flex items-start justify-between gap-2 {{ $position !== null ? 'mt-1' : '' }}">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $waitList->customer_name }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $waitList->phone ?: $waitList->company_name }}</p>
                                </div>
                                <details class="wl-menu relative shrink-0">
                                    <summary class="p-1.5 rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50">
                                        <x-heroicon-o-ellipsis-vertical class="w-4 h-4" />
                                    </summary>
                                    <div class="absolute right-0 z-20 mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg py-1">
                                        <a href="{{ route('admin.wait-list.show', $waitList) }}"
                                            class="block px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">View Details</a>
                                        @if (in_array($waitList->status->value, \App\Enums\WaitList\WaitListStatus::waiting(), true))
                                            <form method="POST" action="{{ route('admin.wait-list.cancel', $waitList) }}"
                                                onsubmit="return confirm('Cancel this wait list record?');">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">Cancel Record</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </div>

                            <div class="mt-3 flex items-center gap-3 bg-gray-50 rounded-lg p-2.5">
                                @if ($thumb)
                                    <img src="{{ $thumb }}" alt="" class="w-12 h-12 rounded-lg object-cover bg-white border border-gray-200 shrink-0">
                                @else
                                    <span class="w-12 h-12 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-300 shrink-0">
                                        <x-heroicon-o-cube class="w-6 h-6" />
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase">Waiting For</p>
                                    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $waitList->demandLabel() }}">{{ $waitList->demandLabel() }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $waitList->request_type->label() }}</p>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase">Store Pref.</p>
                                    <p class="text-xs text-gray-700 mt-0.5 truncate">{{ $waitList->store?->store_name ?? $waitList->store_preference->label() }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase">Added</p>
                                    <p class="text-xs text-gray-700 mt-0.5">{{ $waitList->created_at->format('M j, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase">Alerts</p>
                                    <p class="text-xs mt-0.5">
                                        @if ($waitList->open_alerts_count)
                                            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold">{{ $waitList->open_alerts_count }}</span>
                                        @else
                                            <span class="text-gray-500">0</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase">Priority</p>
                                    <p class="text-xs text-gray-700 mt-0.5">{{ $waitList->priority_override ? '#' . $waitList->priority_override : '—' }}</p>
                                </div>
                            </div>
                        </div>

                        @if ($note !== '')
                            <div class="flex items-center gap-1.5 px-4 py-2 bg-sky-50 border-t border-sky-100 rounded-b-xl">
                                <x-heroicon-o-clipboard-document-list class="w-3.5 h-3.5 text-sky-500 shrink-0" />
                                <p class="text-xs text-sky-800 truncate" title="{{ $note }}">{{ Str::limit($note, 90) }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

        @else

            {{-- List view --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                            <th class="pb-2 pr-3">Priority</th>
                            <th class="pb-2 pr-3">Customer</th>
                            <th class="pb-2 pr-3">Waiting For</th>
                            <th class="pb-2 pr-3">Store Pref.</th>
                            <th class="pb-2 pr-3 text-right">Age</th>
                            <th class="pb-2 pr-3">Added</th>
                            <th class="pb-2 pr-3 text-center">Alerts</th>
                            <th class="pb-2 pr-3">Status</th>
                            <th class="pb-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($waitLists as $waitList)
                            @php
                                $position = $queuePositions[$waitList->id] ?? null;
                                $thumb    = $thumbUrl($waitList);
                            @endphp
                            <tr>
                                <td class="py-2.5 pr-3">
                                    @if ($position !== null)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-md border text-xs font-bold {{ $badgeColor($position) }}">{{ $position }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-3">
                                    <p class="font-medium text-gray-800">{{ $waitList->customer_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $waitList->phone ?: $waitList->company_name }}</p>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <div class="flex items-center gap-2.5">
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="" class="w-9 h-9 rounded-md object-cover bg-gray-50 border border-gray-200 shrink-0">
                                        @else
                                            <span class="w-9 h-9 rounded-md bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-300 shrink-0">
                                                <x-heroicon-o-cube class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <div>
                                            <p class="text-gray-700">{{ $waitList->demandLabel() }}</p>
                                            <p class="text-[11px] text-gray-400">{{ $waitList->request_type->label() }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 pr-3 text-gray-600">{{ $waitList->store?->store_name ?? $waitList->store_preference->label() }}</td>
                                <td class="py-2.5 pr-3 text-right font-medium text-gray-800">{{ $waitList->age_days }}d</td>
                                <td class="py-2.5 pr-3 text-gray-600">{{ $waitList->created_at->format('M j, Y') }}</td>
                                <td class="py-2.5 pr-3 text-center">
                                    @if ($waitList->open_alerts_count)
                                        <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold">{{ $waitList->open_alerts_count }}</span>
                                    @else
                                        <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $waitList->status->color() }}">
                                        {{ $waitList->status->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.wait-list.show', $waitList) }}" class="inline-flex p-1 text-sky-600 hover:text-sky-800" title="View">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </a>
                                    @if (in_array($waitList->status->value, \App\Enums\WaitList\WaitListStatus::waiting(), true))
                                        <details class="wl-menu relative inline-block align-middle">
                                            <summary class="inline-flex p-1 text-gray-400 hover:text-gray-600">
                                                <x-heroicon-o-ellipsis-vertical class="w-4 h-4" />
                                            </summary>
                                            <div class="absolute right-0 z-20 mt-1 w-36 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-left">
                                                <form method="POST" action="{{ route('admin.wait-list.cancel', $waitList) }}"
                                                    onsubmit="return confirm('Cancel this wait list record?');">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">Cancel Record</button>
                                                </form>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif

        {{-- Pagination --}}
        @if ($waitLists->total())
            <div class="flex flex-wrap items-center justify-between gap-3 mt-5 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span>Show</span>
                    <select onchange="window.location = this.value" class="border border-gray-200 rounded-lg px-2 py-1 text-xs bg-white">
                        @foreach ([12, 24, 48] as $size)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => null]) }}"
                                @selected($waitLists->perPage() === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                    <span>Showing {{ $waitLists->firstItem() }} to {{ $waitLists->lastItem() }} of {{ $waitLists->total() }} results</span>
                </div>
                @if ($waitLists->hasPages())
                    <div class="flex items-center gap-1">
                        @if ($waitLists->onFirstPage())
                            <span class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-300"><x-heroicon-o-chevron-left class="w-3.5 h-3.5" /></span>
                        @else
                            <a href="{{ $waitLists->previousPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50"><x-heroicon-o-chevron-left class="w-3.5 h-3.5" /></a>
                        @endif
                        @foreach ($waitLists->getUrlRange(max(1, $waitLists->currentPage() - 2), min($waitLists->lastPage(), $waitLists->currentPage() + 2)) as $page => $url)
                            <a href="{{ $url }}"
                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $page === $waitLists->currentPage() ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $page }}</a>
                        @endforeach
                        @if ($waitLists->hasMorePages())
                            <a href="{{ $waitLists->nextPageUrl() }}" class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50"><x-heroicon-o-chevron-right class="w-3.5 h-3.5" /></a>
                        @else
                            <span class="px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-300"><x-heroicon-o-chevron-right class="w-3.5 h-3.5" /></span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

@endsection

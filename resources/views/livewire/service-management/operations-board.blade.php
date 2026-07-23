{{-- Service Operations Board (Phase 1) — inline-styled to match the approved
     baseline exactly and stay independent of the compiled CSS bundle. Drag
     reorder/reassign persistence lands in Phase 2. --}}
<div wire:poll.{{ $this->pollSeconds() }}s style="font-family: inherit;">

    {{-- KPI tiles --}}
    @php
        $kpis = [
            ['key' => 'open',      'icon' => '🎫', 'label' => 'Open Tickets',  'sub' => 'All unfinished tickets',       'bg' => '#e0f2fe', 'c' => '#0369a1'],
            ['key' => 'emergency', 'icon' => '!',  'label' => 'Emergency',     'sub' => 'Open emergency priority',      'bg' => '#fee2e2', 'c' => '#dc2626'],
            ['key' => 'blocked',   'icon' => '❚❚', 'label' => 'Blocked',       'sub' => 'Waiting on parts / approvals', 'bg' => '#fef3c7', 'c' => '#b45309'],
            ['key' => 'bill',      'icon' => '$',  'label' => 'Ready to Bill',  'sub' => 'Completed, awaiting charges',   'bg' => '#dcfce7', 'c' => '#15803d'],
        ];
    @endphp
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px;">
        @foreach ($kpis as $k)
            @php $on = $kpiFilter === $k['key']; @endphp
            <button type="button" wire:click="toggleKpi('{{ $k['key'] }}')"
                style="position: relative; display: flex; align-items: center; gap: 13px; text-align: left; background: #fff; border: 1px solid {{ $on ? $k['c'] : '#e9edf2' }}; border-radius: 14px; padding: 15px 18px; cursor: pointer; box-shadow: {{ $on ? '0 0 0 3px ' . $k['bg'] : '0 1px 2px rgba(15,23,42,.04)' }}; font-family: inherit;">
                <span style="width: 42px; height: 42px; flex: 0 0 auto; border-radius: 11px; background: {{ $k['bg'] }}; color: {{ $k['c'] }}; display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 700;">{{ $k['icon'] }}</span>
                <span style="min-width: 0;">
                    <span style="display: flex; align-items: baseline; gap: 8px;">
                        <span style="font-size: 24px; font-weight: 700; color: #0f172a; line-height: 1;">{{ $kpiCounts[$k['key']] }}</span>
                        <span style="font-size: 13.5px; font-weight: 600; color: #0f172a;">{{ $k['label'] }}</span>
                    </span>
                    <span style="display: block; font-size: 12px; color: #94a3b8; margin-top: 3px;">{{ $k['sub'] }}</span>
                </span>
                @if ($on)<span style="position: absolute; top: 10px; right: 12px; font-size: 12px; font-weight: 700; color: {{ $k['c'] }};">✓</span>@endif
            </button>
        @endforeach
    </div>

    {{-- Filter row --}}
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px;">
        <select wire:model.live="statusFilter" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 9px 12px; font-size: 13px; color: #475569; cursor: pointer; font-family: inherit;">
            @foreach ($statusOptions as $o)
                <option value="{{ $o[0] }}">{{ $o[1] }}</option>
            @endforeach
        </select>
        <select wire:model.live="priorityFilter" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 9px 12px; font-size: 13px; color: #475569; cursor: pointer; font-family: inherit;">
            <option value="All">All Priorities</option>
            <option value="Emergency">Emergency</option>
            <option value="Normal">Standard</option>
        </select>
        <div wire:click="toggleEmergency" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: {{ $emergencyOnly ? '#0f172a' : '#64748b' }}; font-weight: {{ $emergencyOnly ? 600 : 500 }};">
            <span style="width: 17px; height: 17px; border-radius: 5px; border: 1px solid {{ $emergencyOnly ? '#0d9488' : '#cbd5e1' }}; background: {{ $emergencyOnly ? '#0d9488' : '#fff' }}; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">{{ $emergencyOnly ? '✓' : '' }}</span>
            Emergency only
        </div>
        <div wire:click="toggleAging" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: {{ $aging ? '#0f172a' : '#64748b' }}; font-weight: {{ $aging ? 600 : 500 }};">
            <span style="width: 17px; height: 17px; border-radius: 5px; border: 1px solid {{ $aging ? '#0d9488' : '#cbd5e1' }}; background: {{ $aging ? '#0d9488' : '#fff' }}; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">{{ $aging ? '✓' : '' }}</span>
            Aging (7d+)
        </div>
        <div style="flex: 1;"></div>
        @if ($anyFilter)
            <button type="button" wire:click="clearFilters" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 9px 14px; font-family: inherit; font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer;">Clear filters</button>
        @endif
    </div>

    {{-- Issue-type tabs --}}
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
        <span style="font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; font-weight: 600; margin-right: 4px;">Issue type</span>
        @foreach (['All', 'Damage', 'Maint.', 'Warranty', 'Field'] as $tab)
            @php $active = $typeFilter === $tab; @endphp
            <button type="button" wire:click="setType('{{ $tab }}')"
                style="font-family: inherit; font-size: 13px; font-weight: 600; padding: 7px 13px; border-radius: 999px; cursor: pointer; border: 1px solid {{ $active ? '#0f172a' : '#e2e8f0' }}; background: {{ $active ? '#0f172a' : '#fff' }}; color: {{ $active ? '#fff' : '#475569' }};">{{ $tab }} ({{ $typeCounts[$tab] }})</button>
        @endforeach
        <div style="flex: 1;"></div>
        <span style="font-size: 12px; color: #94a3b8;">Position = priority (leftmost first). Update status on the card · click a card to open its workflow.</span>
    </div>

    {{-- Swimlanes --}}
    <div style="display: flex; flex-direction: column; gap: 14px;">
        @forelse ($lanes as $lane)
            <div style="display: flex; align-items: stretch; background: {{ $lane['bg'] }}; border: 1px solid {{ $lane['band'] }}; border-left: 4px solid {{ $lane['accent'] }}; border-radius: 14px; overflow: hidden;">
                {{-- Lane header --}}
                <div style="flex: 0 0 210px; display: flex; align-items: center; gap: 10px; padding: 16px; background: {{ $lane['band'] }};">
                    <span style="width: 38px; height: 38px; flex: 0 0 auto; border-radius: 50%; background: #fff; color: {{ $lane['accent'] }}; border: 1px solid {{ $lane['accent'] }}; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">{{ $lane['initials'] }}</span>
                    <div style="min-width: 0;">
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.2;">{{ $lane['name'] }}</div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 1px;">{{ $lane['role'] }}</div>
                    </div>
                    <span style="margin-left: auto; font-size: 12px; font-weight: 700; color: {{ $lane['accent'] }}; background: #fff; border: 1px solid {{ $lane['accent'] }}; border-radius: 999px; padding: 4px 10px;">{{ $lane['count'] }}</span>
                </div>

                {{-- Card strip --}}
                <div style="flex: 1; min-width: 0; display: flex; align-items: stretch; gap: 12px; padding: 16px; overflow-x: auto;">
                    @foreach ($lane['cards'] as $card)
                        <div wire:key="card-{{ $card['id'] }}" style="width: 258px; flex: 0 0 auto; background: #fff; border: 1px solid #e9edf2; border-radius: 13px; box-shadow: 0 1px 3px rgba(15,23,42,.06); overflow: hidden; display: flex; flex-direction: column;">
                            @if ($card['emergency'])
                                <div style="background: #dc2626; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-align: center; padding: 4px;">EMERGENCY</div>
                            @endif
                            <a href="{{ $card['showUrl'] }}" style="display: block; padding: 13px 14px 10px; text-decoration: none; color: inherit;">
                                <div style="display: flex; align-items: center; gap: 9px; margin-bottom: 11px;">
                                    <span style="flex: 0 0 auto; width: 26px; height: 26px; border-radius: 8px; background: {{ $card['ordinal'] === 1 ? '#0f172a' : '#eef2f7' }}; color: {{ $card['ordinal'] === 1 ? '#fff' : '#64748b' }}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $card['ordinal'] }}</span>
                                    @if ($card['ordinal'] === 1)
                                        <span style="font-size: 9.5px; font-weight: 700; letter-spacing: .1em; color: #0d9488; background: #d3f4ec; border-radius: 999px; padding: 3px 8px;">NEXT UP</span>
                                    @endif
                                    <span style="margin-left: auto; font-size: 11px; font-weight: 600; color: {{ $card['ageOld'] ? '#d97706' : '#94a3b8' }};">{{ $card['ageLabel'] }}</span>
                                </div>
                                <div style="font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.25;">{{ $card['equip'] }}</div>
                                <div style="font-size: 11.5px; color: #94a3b8; margin: 1px 0 10px;">{{ $card['equipId'] }}</div>
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 11px; flex-wrap: wrap;">
                                    <span style="font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 999px; background: {{ $card['typeBg'] }}; color: {{ $card['typeC'] }}; white-space: nowrap;">{{ $card['typeLabel'] }}</span>
                                    @if ($card['field'])
                                        <span style="font-size: 10px; font-weight: 700; color: #7c3aed; background: #f3e8ff; border-radius: 5px; padding: 2px 6px;">◈ Field</span>
                                    @elseif ($card['warranty'])
                                        <span style="font-size: 10px; font-weight: 700; color: #4f46e5; background: #e0e7ff; border-radius: 5px; padding: 2px 6px;">OEM</span>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: #64748b;">
                                    <span style="font-weight: 600; color: #475569;">{{ $card['ticket'] }}</span>
                                    <span style="color: #cbd5e1;">·</span>
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $card['customer'] }}</span>
                                </div>
                            </a>
                            <div style="padding: 0 14px 13px;">
                                <select wire:change="setStatus({{ $card['id'] }}, $event.target.value)"
                                    style="font-family: inherit; font-size: 11.5px; font-weight: 600; color: {{ $card['statusC'] }}; background: {{ $card['statusBg'] }}; border: 1px solid {{ $card['statusBd'] }}; border-radius: 8px; padding: 6px 10px; cursor: pointer; width: 100%;">
                                    @foreach ($cardStatuses as $s)
                                        <option value="{{ $s['value'] }}" @selected($s['value'] === $card['status'])>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div style="background: #fff; border: 1px dashed #e2e8f0; border-radius: 14px; padding: 40px; text-align: center; color: #94a3b8; font-size: 14px;">
                No service tickets match the current filters.
            </div>
        @endforelse
    </div>
</div>

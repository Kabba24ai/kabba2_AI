{{--
    Shared "Assigned Personnel" intake component — ONE assignment method reused
    by every Service intake (Standard Service ticket + Field Service mission).
    Multiple assigned personnel, one optional Team Leader, the shared HRM
    employee list, Assign-Now expand/collapse, old-input restoration, and the
    at-a-glance avatar chips all live here so the two forms can never drift into
    two personnel workflows.

    Contract (identical on both intakes):
      personnel[]     — checked employee ids
      team_leader_id  — the one lead (must be among personnel; enforced server-side)

    Params:
      $prefix     — id/class namespace ('st' | 'fs') so two instances never collide
      $employees  — HRM employees (id, first_name, last_name), from User::active()

    Pair with window.servicePersonnelAssignment.init($prefix) (defined once below).
--}}
@php
    // Self-contained restoration across a validation round-trip — the host view
    // does not need to pre-compute this.
    $selectedPersonnel = collect(old('personnel', []))->map(fn ($v) => (int) $v)->all();
@endphp

<div class="flex items-center justify-between gap-4">
    <div class="flex items-center gap-2 min-w-0">
        <span class="w-9 h-9 rounded-full bg-gray-100 border border-gray-200 text-gray-400 flex items-center justify-center shrink-0">
            <x-heroicon-o-user-group class="w-5 h-5" />
        </span>
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-gray-800">Assigned Personnel</h2>
            <p class="text-xs text-gray-400 truncate" id="{{ $prefix }}-crew-summary">No one assigned yet.</p>
        </div>
    </div>
    <button type="button" id="{{ $prefix }}-assign-toggle"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-sm font-medium text-blue-700 hover:bg-blue-100 transition shrink-0">
        <x-heroicon-o-user-plus class="w-4 h-4" />
        Assign Now
    </button>
</div>

<div id="{{ $prefix }}-assign-panel" class="hidden mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
    <p class="text-xs text-gray-500 mb-3">
        Check everyone working this ticket, then mark one person as <span class="font-semibold">Team Leader</span>.
        Employees come from HRM records.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach ($employees as $employee)
            <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2">
                <label class="flex items-center gap-2 cursor-pointer min-w-0">
                    <input type="checkbox" name="personnel[]" value="{{ $employee->id }}"
                        class="{{ $prefix }}-crew-check text-blue-600 rounded focus:ring-blue-500"
                        data-name="{{ $employee->first_name }} {{ $employee->last_name }}"
                        @checked(in_array($employee->id, $selectedPersonnel, true))>
                    <span class="text-sm text-gray-700 truncate">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                </label>
                <label class="flex items-center gap-1 cursor-pointer shrink-0" title="Team Leader">
                    <input type="radio" name="team_leader_id" value="{{ $employee->id }}"
                        class="{{ $prefix }}-leader-radio text-amber-500 focus:ring-amber-400"
                        @checked((int) old('team_leader_id') === $employee->id)
                        @disabled(!in_array($employee->id, $selectedPersonnel, true))>
                    <span class="text-[11px] font-semibold text-amber-600 uppercase">Lead</span>
                </label>
            </div>
        @endforeach
    </div>
    @error('team_leader_id')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
    @error('personnel')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
</div>

{{-- Assigned team at a glance — avatar chips in the same language as
     partials/_personnel_avatars. Display only; the checkboxes above remain the
     editing controls and the JS keeps this in sync. --}}
<div id="{{ $prefix }}-crew-display" class="hidden mt-4 pt-4 border-t border-gray-100">
    <div id="{{ $prefix }}-crew-lead-wrap" class="hidden mb-3">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Lead Technician</p>
        <div id="{{ $prefix }}-crew-lead" class="flex flex-wrap gap-2"></div>
    </div>
    <div id="{{ $prefix }}-crew-team-wrap" class="hidden">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Assigned Team</p>
        <div id="{{ $prefix }}-crew-team" class="flex flex-wrap gap-2"></div>
    </div>
</div>

@once
@push('js')
<script>
// Shared Assigned Personnel engine — defined ONCE per page, driven per instance
// by init(prefix). Owns: Assign-Now expand/collapse, the Team Leader radio
// rules (a lead must be a selected person; unchecking a person clears their
// lead flag), the summary line, and the at-a-glance avatar chips.
window.servicePersonnelAssignment = (function () {
    function init(prefix) {
        const panel   = document.getElementById(prefix + '-assign-panel');
        const summary = document.getElementById(prefix + '-crew-summary');
        const toggle  = document.getElementById(prefix + '-assign-toggle');
        if (!panel || !summary || !toggle) return { sync: function () {} };

        toggle.addEventListener('click', function () { panel.classList.toggle('hidden'); });

        // Avatar chip in the same language as partials/_personnel_avatars:
        // initials in a w-7 rounded-full circle; the lead swaps blue for amber.
        const crewDisplay  = document.getElementById(prefix + '-crew-display');
        const crewLeadWrap = document.getElementById(prefix + '-crew-lead-wrap');
        const crewLeadBox  = document.getElementById(prefix + '-crew-lead');
        const crewTeamWrap = document.getElementById(prefix + '-crew-team-wrap');
        const crewTeamBox  = document.getElementById(prefix + '-crew-team');

        function crewChip(name, isLead) {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-2 rounded-full border py-1 pl-1 pr-3 '
                + (isLead ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50');

            const avatar = document.createElement('span');
            avatar.className = 'w-7 h-7 rounded-full text-[10px] font-bold flex items-center justify-center ring-2 ring-white '
                + (isLead ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700');
            avatar.textContent = name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
            avatar.title = name;
            chip.appendChild(avatar);

            const label = document.createElement('span');
            label.className = 'text-sm font-medium ' + (isLead ? 'text-amber-800' : 'text-gray-700');
            label.textContent = name;
            chip.appendChild(label);

            if (isLead) {
                const badge = document.createElement('span');
                badge.className = 'text-[10px] font-bold text-amber-600 uppercase tracking-wide';
                badge.textContent = 'Lead';
                chip.appendChild(badge);
            }
            return chip;
        }

        function syncCrew() {
            const checks  = Array.from(document.querySelectorAll('.' + prefix + '-crew-check'));
            const checked = checks.filter(box => box.checked);
            let leaderName = null;

            checks.forEach(function (box) {
                const radio = box.closest('div').querySelector('.' + prefix + '-leader-radio');
                if (!radio) return;
                radio.disabled = !box.checked;
                if (!box.checked && radio.checked) radio.checked = false;
                if (radio.checked) leaderName = box.dataset.name;
            });

            summary.textContent = checked.length === 0
                ? 'No one assigned yet.'
                : checked.length + ' assigned' + (leaderName ? ' · Team Leader: ' + leaderName : ' · no team leader marked');

            // Rebuild the at-a-glance chips: lead first, then the rest of the team.
            crewLeadBox.replaceChildren();
            crewTeamBox.replaceChildren();
            checked.forEach(function (box) {
                const isLead = box.closest('div').querySelector('.' + prefix + '-leader-radio').checked;
                (isLead ? crewLeadBox : crewTeamBox).appendChild(crewChip(box.dataset.name, isLead));
            });
            crewLeadWrap.classList.toggle('hidden', !leaderName);
            crewTeamWrap.classList.toggle('hidden', !crewTeamBox.childElementCount);
            crewDisplay.classList.toggle('hidden', checked.length === 0);
        }

        document.querySelectorAll('.' + prefix + '-crew-check, .' + prefix + '-leader-radio').forEach(function (el) {
            el.addEventListener('change', syncCrew);
        });
        syncCrew();

        // Keep the panel open if a crew was already selected (validation round-trip).
        if (document.querySelector('.' + prefix + '-crew-check:checked')) panel.classList.remove('hidden');

        return { sync: syncCrew };
    }
    return { init: init };
})();
</script>
@endpush
@endonce

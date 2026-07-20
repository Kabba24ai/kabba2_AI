{{--
    Charge Alerts summary donut cards (Dashboard V2 Phase 1B). Polls every 30s;
    Blade numbers refresh on poll and the donut canvases update via the
    'charge-alerts-updated' browser event (see index.blade.php). Detailed
    records live on the deep-linked Fuel / Damage tabs of the Dash Reports page.
--}}
<div wire:poll.30s="refreshAlerts" class="grid grid-cols-1 lg:grid-cols-2 gap-8">

    {{-- Fuel Charge Alerts. Split footer (approved design): outlined
         "+ New Fuel Charge" secondary opens the shared creation modal
         (included by index.blade.php); filled Resolve stays primary.
         Subtitle intentionally removed per the approved card design. --}}
    <x-admin.dashboard.charge-alert-card
        title="Fuel Charge Alerts"
        icon-gradient="from-orange-500 to-orange-600"
        accent-hex="#f97316"
        chart-id="fuel-charge-donut"
        action-class="bg-orange-500 hover:bg-orange-600"
        :href="route('admin.reports.fuel-charge-workspace.index')"
        action-label="Resolve Fuel Charges"
        secondary-label="New Fuel Charge"
        secondary-id="fuel-new-charge-btn"
        :outstanding="$fuelSummary['outstanding'] ?? 0"
        :completed-today="$fuelSummary['completed_today'] ?? 0"
        :resolved-this-week="$fuelSummary['resolved_this_week'] ?? 0"
        :new-today="$fuelSummary['new_today'] ?? 0"
        :avg-age-days="$fuelSummary['avg_age_days'] ?? 0">
        <x-slot:icon>
            <svg fill="currentColor" class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M96 128C96 92.7 124.7 64 160 64L320 64C355.3 64 384 92.7 384 128L384 320L392 320C440.6 320 480 359.4 480 408L480 440C480 453.3 490.7 464 504 464C517.3 464 528 453.3 528 440L528 286C500.4 278.9 480 253.8 480 224L480 164.5L454.2 136.2C445.3 126.4 446 111.2 455.8 102.3C465.6 93.4 480.8 94.1 489.7 103.9L561.4 182.7C570.8 193 576 206.4 576 220.4L576 440C576 479.8 543.8 512 504 512C464.2 512 432 479.8 432 440L432 408C432 385.9 414.1 368 392 368L384 368L384 529.4C393.3 532.7 400 541.6 400 552C400 565.3 389.3 576 376 576L104 576C90.7 576 80 565.3 80 552C80 541.5 86.7 532.7 96 529.4L96 128zM160 144L160 240C160 248.8 167.2 256 176 256L304 256C312.8 256 320 248.8 320 240L320 144C320 135.2 312.8 128 304 128L176 128C167.2 128 160 135.2 160 144z"/></svg>
        </x-slot:icon>
    </x-admin.dashboard.charge-alert-card>

    {{-- Damage Alerts --}}
    <x-admin.dashboard.charge-alert-card
        title="Damage Alerts"
        subtitle="Damage alerts requiring review"
        icon-gradient="from-red-500 to-red-600"
        accent-hex="#ef4444"
        chart-id="damage-charge-donut"
        action-class="bg-red-500 hover:bg-red-600"
        :href="route('admin.reports.calls-log.index') . '?tab=damage'"
        action-label="Resolve Damage"
        :outstanding="$damageSummary['outstanding'] ?? 0"
        :completed-today="$damageSummary['completed_today'] ?? 0"
        :resolved-this-week="$damageSummary['resolved_this_week'] ?? 0"
        :new-today="$damageSummary['new_today'] ?? 0"
        :avg-age-days="$damageSummary['avg_age_days'] ?? 0">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" class="w-6 h-6 text-white"><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
        </x-slot:icon>
    </x-admin.dashboard.charge-alert-card>

</div>

<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Services\Alerts\ChargeAlertQueue;

class AlertsSection extends Component
{
    protected $listeners = ['refreshAlerts'];

    /**
     * Canonical Charge Alerts summary payloads consumed by the dashboard cards.
     *
     * Dashboard V2 Phase 1A: the queue construction and metric math moved
     * VERBATIM to App\Services\Alerts\ChargeAlertQueue so the Fuel/Damage
     * operational workspaces consume the exact same calculations — dashboard
     * cards and workspace counts reconcile by construction, never by
     * parallel implementations. Nothing about the values, inclusion rules,
     * dedup, timezone handling, or card wiring changed in this move.
     */
    public array $fuelSummary   = ['outstanding' => 0, 'completed_today' => 0, 'resolved_this_week' => 0, 'new_today' => 0, 'avg_age_days' => 0];
    public array $damageSummary = ['outstanding' => 0, 'completed_today' => 0, 'resolved_this_week' => 0, 'new_today' => 0, 'avg_age_days' => 0];

    public function refreshAlerts()
    {
        $this->fuelSummary   = ChargeAlertQueue::summarize(ChargeAlertQueue::fuelAlerts(), 'fuel');
        $this->damageSummary = ChargeAlertQueue::summarize(ChargeAlertQueue::damageAlerts(), 'damage');

        // Feed the donut canvases (wire:ignore) with fresh values on mount and
        // on each 30s poll. Blade-rendered numbers refresh via the poll itself.
        $this->dispatch('charge-alerts-updated', charts: [
            'fuel-charge-donut'   => ['outstanding' => $this->fuelSummary['outstanding'],   'completed' => $this->fuelSummary['completed_today']],
            'damage-charge-donut' => ['outstanding' => $this->damageSummary['outstanding'], 'completed' => $this->damageSummary['completed_today']],
        ]);
    }

    public function mount()
    {
        $this->refreshAlerts();
    }

    public function render()
    {
        return view('livewire.dashboard.alerts-section');
    }
}

<?php

namespace App\Livewire\Dashboard;

use App\Models\WaitList\EquipmentWaitList;
use App\Services\WaitList\WaitListStats;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Dashboard "Wait List Opportunities" card — notification and navigation
 * only. It answers exactly one question: is there a customer we should
 * contact because suitable equipment has returned?
 *
 * Every count comes from the canonical WaitListStats service; this
 * component never reconstructs the opportunity calculation. The primary
 * number is contactOpportunities(): DISTINCT active wait-list records
 * with at least one unresolved match — one customer matched by three
 * returned units is ONE opportunity. Awaiting Conversion is shown
 * separately because an accepted customer is an unsecured sale, not a
 * contact task. All workflow (dispositions, conversion, substitution
 * review) lives on the Wait List page, never here.
 */
class WaitListOpportunities extends Component
{
    private const PREVIEW_LIMIT = 2;

    /** Server-derived access flag — locked so a client update can never flip it. */
    #[Locked]
    public bool $canAccess = false;

    public int $contactOpportunities = 0;
    public int $newToday = 0;
    public int $awaitingConversion = 0;

    /** @var array<int, array{customer: string, demand: string, units: int}> */
    public array $preview = [];
    public int $moreCount = 0;

    public function mount(): void
    {
        $this->refreshOpportunities();
    }

    /**
     * The module's canonical authorization: the same wait_list.view ability
     * that gates every admin wait-list route (Spatie permission middleware).
     * Route::has stays only as a deployment-safety guard — authorization
     * itself is the Gate check, which the global small-business
     * Gate::before bypass currently grants to every signed-in user and
     * denies to guests, and which enforces granular roles automatically if
     * the bypass is ever removed.
     */
    protected function hasWaitListAccess(): bool
    {
        return Route::has('admin.wait-list.index')
            && (auth()->user()?->can('wait_list.view') ?? false);
    }

    /**
     * Runs on mount AND on every poll/refresh request — authorization is
     * re-resolved server-side each time, so no hydrated request can read
     * data the current user is no longer permitted to see.
     */
    public function refreshOpportunities(): void
    {
        $this->canAccess = $this->hasWaitListAccess();

        if (! $this->canAccess) {
            $this->reset('contactOpportunities', 'newToday', 'awaitingConversion', 'preview', 'moreCount');

            return;
        }

        $this->contactOpportunities = WaitListStats::contactOpportunities();
        $this->newToday             = WaitListStats::newOpportunitiesToday();
        $this->awaitingConversion   = WaitListStats::acceptedAwaitingConversion();

        // Compact preview: the most urgent DISTINCT customer requests with an
        // unresolved match — never raw alert rows, so one request with
        // several returned matching UNITS renders once with a unit count
        // (each count reflects individual returned Equipment IDs).
        $this->preview = EquipmentWaitList::waiting()
            ->whereHas('alerts', fn ($a) => $a->open())
            ->withCount(['alerts as open_matches_count' => fn ($a) => $a->open()])
            ->with(['category:id,title', 'items.equipment:id,equipment_name'])
            ->byUrgency()
            ->limit(self::PREVIEW_LIMIT)
            ->get()
            ->map(fn ($waitList) => [
                'customer' => $waitList->company_name ?: $waitList->customer_name,
                'demand'   => $waitList->category?->title
                    ?? $waitList->items->first()?->equipment?->equipment_name
                    ?? 'Equipment request',
                'units'    => (int) $waitList->open_matches_count,
            ])
            ->all();

        $this->moreCount = max(0, $this->contactOpportunities - count($this->preview));
    }

    public function render()
    {
        return view('livewire.dashboard.wait-list-opportunities');
    }
}

<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcastEvent;
use Illuminate\Http\Request;

/**
 * Broadcast Queue — actual broadcast events, separate from the Message
 * Library. Active view holds working records plus completed items for
 * their 7-day window; Archive view holds everything auto/manually
 * archived, searchable and filterable.
 */
class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $view = $request->input('view') === 'archive' ? 'archive' : 'active';

        $query = SmsBroadcastEvent::query()
            ->with('createdBy')
            ->when($view === 'archive', fn ($q) => $q->archived(), fn ($q) => $q->active());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('message_name', 'LIKE', "%{$term}%")
                    ->orWhere('audience_description', 'LIKE', "%{$term}%");
            });
        }

        // Active work first: drafts/awaiting/scheduled/sending, then recents
        $statusOrder = "FIELD(status, 'sending', 'awaiting_confirmation', 'scheduled', 'draft', 'sent', 'partially_sent', 'failed', 'cancelled')";
        $events = $query
            ->when($view === 'active', fn ($q) => $q->orderByRaw($statusOrder))
            ->orderByDesc('updated_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.crm.message_management.broadcast_queue.index', [
            'events'   => $events,
            'view'     => $view,
            'statuses' => SmsBroadcastStatus::cases(),
        ]);
    }
}

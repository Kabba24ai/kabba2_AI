<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Enums\Routing\RouteStatus;
use App\Http\Controllers\Controller;
use App\Services\FieldService\FieldDispatchRoutePlanner;
use Illuminate\Http\Request;

/**
 * Field Service's own authenticated endpoint for the live Dispatch Logistics
 * arrival preview (browser → Kabba → Google). This is intentionally a
 * MODULE-SPECIFIC controller over the shared FieldDispatchRoutePlanner /
 * RoutingService — NOT a generic, unrestricted routing endpoint exposed to the
 * browser. Dispatch will later add its own analogous controller over the same
 * shared services.
 *
 * Returns only display values (travel time, expected arrival, normalized
 * status/message). The API key never leaves the server.
 */
class RoutePreviewController extends Controller
{
    public function __invoke(Request $request, FieldDispatchRoutePlanner $planner)
    {
        $data = $request->only([
            'departure_location_type', 'departure_store_id',
            'departure_street', 'departure_line2', 'departure_city', 'departure_state', 'departure_zip',
            'destination_address', 'departure_at',
        ]);

        if (!$planner->isConfigured()) {
            return response()->json([
                'configured'       => false,
                'success'          => false,
                'status'           => RouteStatus::NotConfigured->value,
                'message'          => 'Routing is not configured — expected arrival is unavailable. The ticket can still be created.',
                'travel_time'      => null,
                'expected_arrival' => null,
            ]);
        }

        $result  = $planner->estimate($data);
        $success = $result->status->isOk();

        return response()->json([
            'configured'       => true,
            'success'          => $success,
            'status'           => $result->status->value,
            'message'          => $success ? null : $result->status->label(),
            'travel_time'      => $success ? $result->durationForHumans() : null,
            'expected_arrival' => ($success && $result->expectedArrival)
                ? $result->expectedArrival->timezone(config('app.timezone'))->format('M j, Y \a\t g:i A')
                : null,
        ]);
    }
}

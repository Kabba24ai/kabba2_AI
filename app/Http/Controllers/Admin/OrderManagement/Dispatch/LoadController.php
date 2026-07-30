<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchLoad;
use App\Services\Dispatch\DispatchLoadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Combined-dispatch loads: create (combine), reassign to a driver, remove a member,
 * and ungroup. All grouping/priority/assignment work is delegated to
 * {@see DispatchLoadService} (server-authoritative, atomic). Business-rule
 * violations surface as 422 with a message.
 */
class LoadController extends Controller
{
    public function __construct(private DispatchLoadService $service)
    {
    }

    /** Combine selected order_product legs into one load on a driver. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'member_uids'   => 'required|array|min:2',
            'member_uids.*' => 'string',
            'leg'           => 'required|in:delivery,return,both',
            'driver_id'     => 'required|integer',
        ]);

        $driverId = (int) $data['driver_id'];

        try {
            if ($data['leg'] === 'both') {
                // One atomic operation: a delivery-load AND a return-load for the same
                // items on the same driver. If either leg is invalid, neither is written.
                DB::transaction(function () use ($data, $driverId) {
                    $this->service->combine($data['member_uids'], 'delivery', $driverId, auth()->id());
                    $this->service->combine($data['member_uids'], 'return', $driverId, auth()->id());
                });
            } else {
                $this->service->combine($data['member_uids'], $data['leg'], $driverId, auth()->id());
            }
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /** Reassign the whole load (and its members) to another driver. */
    public function assign(Request $request, DispatchLoad $load)
    {
        $data = $request->validate(['driver_id' => 'required|integer']);

        try {
            $this->service->assignDriver($load, (int) $data['driver_id']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /** Remove a single member from the load (dissolves the load if <2 remain). */
    public function removeMember(Request $request, DispatchLoad $load)
    {
        $data = $request->validate(['member_uid' => 'required|string']);
        $this->service->removeMember($load, $data['member_uid']);

        return response()->json(['success' => true]);
    }

    /** Ungroup the load entirely; members stay on the driver. */
    public function destroy(DispatchLoad $load)
    {
        $this->service->ungroup($load);

        return response()->json(['success' => true]);
    }
}

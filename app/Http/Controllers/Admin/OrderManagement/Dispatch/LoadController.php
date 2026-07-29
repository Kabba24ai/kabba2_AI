<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchLoad;
use App\Services\Dispatch\DispatchLoadService;
use Illuminate\Http\Request;

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
            'leg'           => 'required|in:delivery,return',
            'driver_id'     => 'required|integer',
        ]);

        try {
            $load = $this->service->combine(
                $data['member_uids'],
                $data['leg'],
                (int) $data['driver_id'],
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'load' => ['unique_id' => $load->unique_id]]);
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

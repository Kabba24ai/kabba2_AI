<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Enums\Orders\OrderPaymentStatus;
use Carbon\Carbon;
use App\Models\Orders\OrderProduct;
use Illuminate\Support\Facades\Log;
use App\Models\MaintenanceManagement\Equipment;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;

// Events
use App\Events\Admin\Orders\OrderNoteEvent;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\Notes\PostRequest;

use App\Helpers\CustomHelper;


use Illuminate\Support\Facades\DB;

class NotesStoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($uniqueId, PostRequest $request)
    {

        // Handle the creation of a new note
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $uniqueId)->firstOrFail();

            $order->notes()->create([
                'note' => $validated['note'],
                'user_id' => $validated['user_id'],
                'note_type' => 'dashboard',
                'created_by_type' => auth()->user() ? get_class(auth()->user()) : null,
                'created_by_id' => $validated['user_id'],
            ]);
            $user = auth()->user();
            $typeOfAction = 'created';
            // Fire event for the new note
            event(new OrderNoteEvent($order, $user, $typeOfAction, $order->notes()->latest()->first()));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note created successfully.',
        ], 201);
    }


}

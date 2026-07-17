<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsBroadcast;
use Illuminate\Http\JsonResponse;

/**
 * Archive / restore a reusable library message. Archiving never touches
 * broadcast history — every event carries its own frozen snapshot.
 */
class ArchiveController extends Controller
{
    public function __invoke(int $id): JsonResponse
    {
        $message = SmsBroadcast::findOrFail($id);

        $message->update(['archived_at' => $message->archived_at ? null : now()]);

        return response()->json([
            'success' => true,
            'message' => $message->archived_at ? 'Message archived.' : 'Message restored.',
            'archived' => (bool) $message->archived_at,
        ]);
    }
}

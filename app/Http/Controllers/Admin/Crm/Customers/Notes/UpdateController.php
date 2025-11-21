<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Notes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\CustomerNote;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    public function __invoke(Request $request, CustomerNote $note)
    {
        // Log incoming request (optional)
        Log::info('Updating customer note...', [
            'note_id'  => $note->id,
            'new_text' => $request->text,
            'new_user_id' => $request->user_id,
            'old_values' => $note->only(['description', 'created_by']),
        ]);

        $request->validate([
            'text' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $note->update([
            'description' => $request->text,
            'created_by'  => $request->user_id,
        ]);

        // Log after update
        Log::info('Customer note updated successfully.', [
            'note_id' => $note->id,
            'updated_values' => $note->only(['description', 'created_by']),
        ]);

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Notes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\CustomerNote;

class UpdateController extends Controller
{
    public function __invoke(Request $request, CustomerNote $note)
    {
        $request->validate([
            'text' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $note->update([
            'description' => $request->text,
            'created_by'  => $request->user_id,
        ]);

        return response()->json(['success' => true]);
    }
}

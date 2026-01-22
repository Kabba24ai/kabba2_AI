<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Notes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;

class StoreController extends Controller
{
    public function __invoke(Request $request, Customer $customer)
    {
        $request->validate([
            'text' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        // Map frontend fields to database fields
        $note = $customer->notes()->create([
            'description' => $request->text,
            'created_by'  => $request->user_id,
        ]);

        return response()->json([
            'success' => true,
            'note' => [
                'id' => $note->id,
                'description' => $note->description,
                'created_by' => $note->created_by,
                'created_date' => $note->created_date,
                'created_time' => $note->created_time,
            ],
        ]);
    }
}

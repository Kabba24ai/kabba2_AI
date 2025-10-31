<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Notes;


use App\Http\Controllers\Controller;
use App\Models\Customers\Tag;
use App\Models\Customers\Customer;


use Illuminate\Http\JsonResponse;

class FetchController extends Controller
{
    public function __invoke(Customer $customer): JsonResponse
    {
       $notes = $customer->notes()
        ->with('user')
        ->latest()
        ->get()
        ->map(fn($note) => [
            'id' => $note->id,
            'text' => $note->description,
            'user_id' => $note->created_by,
            'user_name' => $note->user->full_name,
            'created_at' => $note->created_at->format('Y-m-d H:i'),
            'updated_at' => $note->updated_at?->format('Y-m-d H:i'),
        ]);

    return response()->json(['success' => true, 'notes' => $notes]);
    }
}

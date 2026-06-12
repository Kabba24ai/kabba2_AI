<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Notes;


use App\Http\Controllers\Controller;
use App\Models\Customers\Tag;
use App\Models\Customers\Customer;

use App\Helpers\CustomHelper;


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
            'created_at' => CustomHelper::formatDateTime($note->created_at),
            'updated_at' => CustomHelper::formatDateTime($note->updated_at),
        ]);

    return response()->json(['success' => true, 'notes' => $notes]);
    }
}

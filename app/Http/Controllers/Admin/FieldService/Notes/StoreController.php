<?php

namespace App\Http\Controllers\Admin\FieldService\Notes;

use App\Http\Controllers\Controller;
use App\Models\FieldService\FieldServiceTicket;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request, FieldServiceTicket $ticket)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->notes()->create($validated + ['created_by' => auth()->id()]);

        flash('Note added.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}

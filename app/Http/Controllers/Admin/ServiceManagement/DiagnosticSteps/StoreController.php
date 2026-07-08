<?php

namespace App\Http\Controllers\Admin\ServiceManagement\DiagnosticSteps;

use App\Enums\Service\DiagnosticStepOutcome;
use App\Enums\Service\DiagnosticStepType;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'step_type'   => ['required', Rule::enum(DiagnosticStepType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'outcome'     => ['required', Rule::enum(DiagnosticStepOutcome::class)],
        ]);

        $ticket->diagnosticSteps()->create($validated + ['created_by' => auth()->id()]);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Diagnostic step recorded on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}

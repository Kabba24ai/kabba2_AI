<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Service\ServiceResponsibilityDecision;
use App\Models\Service\ServiceTicket;

class ShowController extends Controller
{
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->load([
            'personnel', 'equipment.documentImages.media', 'equipment.assignedProduct.media',
            'order', 'customer', 'createdBy', 'serviceStore',
            'diagnosticSteps.createdBy', 'notes.createdBy', 'notes.media.uploadedBy',
            'approvedByUser', 'repairAuthorizedBy', 'depositOverrideBy', 'responsibilityDecidedBy', 'authorizationOverrideBy',
            'responsibilityDecision',
            'laborEntries.employee', 'chargeLines', 'partsUsed',
            'media.mediaAsset', 'media.uploadedBy',
            'events' => fn ($q) => $q->with('user')->latest()->latest('id'),
            'settlements.extraCharge', 'settlements.createdBy',
        ]);

        // Employee dropdown for the inline labor form — same source as the ticket forms.
        $employees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        // Responsibility stage dropdown — active master records, alphabetical.
        $responsibilityOptions = ServiceResponsibilityDecision::active()->ordered()->get();

        return view('admin.service_management.tickets.show', compact('ticket', 'employees', 'responsibilityOptions'));
    }
}

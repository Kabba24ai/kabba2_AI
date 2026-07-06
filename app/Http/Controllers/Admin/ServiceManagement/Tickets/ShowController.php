<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Service\ServiceTicket;

class ShowController extends Controller
{
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->load([
            'personnel', 'equipment', 'order', 'customer', 'createdBy',
            'approvedByUser', 'repairAuthorizedBy', 'depositOverrideBy', 'responsibilityDecidedBy', 'authorizationOverrideBy',
            'laborEntries.employee', 'chargeLines', 'partsUsed',
            'media.mediaAsset', 'media.uploadedBy',
            'events' => fn ($q) => $q->with('user')->latest()->latest('id'),
            'settlements.extraCharge', 'settlements.createdBy',
        ]);

        // Employee dropdown for the inline labor form — same source as the ticket forms.
        $employees = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.service_management.tickets.show', compact('ticket', 'employees'));
    }
}

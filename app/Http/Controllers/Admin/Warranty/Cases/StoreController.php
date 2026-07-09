<?php

namespace App\Http\Controllers\Admin\Warranty\Cases;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Enums\Warranty\WarrantyPath;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Warranty\SaveWarrantyCaseRequest;
use App\Models\Service\ServiceTicket;
use App\Models\Warranty\WarrantyCase;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(SaveWarrantyCaseRequest $request)
    {
        $validated = $request->validated();
        $path      = WarrantyPath::from($validated['path']);

        $case = DB::transaction(function () use ($request, $validated, $path) {
            // Warranty authorizes work; the Service Ticket performs it. The
            // ticket is created through the normal service lifecycle with
            // OEM-warranty defaults — no Service Module code changes.
            $ticket = ServiceTicket::create([
                'service_type'             => ServiceType::OemWarrantyRepair,
                'service_location'         => ServiceLocation::InShop,
                'priority'                 => ServicePriority::Normal,
                'repair_status'            => RepairStatus::Open,
                'financial_responsibility' => FinancialResponsibility::Pending,
                'financial_status'         => FinancialStatus::NotBillable,
                'opened_at'                => now()->format('Y-m-d'),
                'equipment_id'             => $path === WarrantyPath::Internal ? $validated['equipment_id'] : null,
                'customer_id'              => $path === WarrantyPath::External ? $validated['customer_id'] : null,
                'customer_complaint'       => $validated['complaint'],
                'internal_notes'           => 'Opened by Warranty Case intake — '
                    . $validated['manufacturer'] . ' ' . $validated['model']
                    . ' · SN ' . $validated['serial_number'],
                'created_by'               => auth()->id(),
            ]);

            $case = WarrantyCase::create([
                'path'                         => $path,
                'customer_id'                  => $path === WarrantyPath::External ? $validated['customer_id'] : null,
                'equipment_id'                 => $path === WarrantyPath::Internal ? $validated['equipment_id'] : null,
                'manufacturer'                 => $validated['manufacturer'],
                'model'                        => $validated['model'],
                'serial_number'                => $validated['serial_number'],
                'engine_serial_number'         => $validated['engine_serial_number'] ?? null,
                'has_hour_meter'               => $request->boolean('has_hour_meter'),
                'hours'                        => $request->boolean('has_hour_meter') ? ($validated['hours'] ?? null) : null,
                'purchase_date'                => $validated['purchase_date'] ?? null,
                'selling_dealer'               => $validated['selling_dealer'] ?? null,
                'warranty_registration_number' => $validated['warranty_registration_number'] ?? null,
                'complaint'                    => $validated['complaint'],
                'internal_notes'               => $validated['internal_notes'] ?? null,
                'diagnostic_fee_amount'        => $path === WarrantyPath::External ? ($validated['diagnostic_fee_amount'] ?? null) : null,
                'diagnostic_fee_taxable'       => $request->boolean('diagnostic_fee_taxable', true),
                'service_ticket_id'            => $ticket->id,
                'created_by'                   => auth()->id(),
            ]);

            // Fee marked collected at intake (state recording only in Phase 1)
            if ($path === WarrantyPath::External && $request->boolean('diagnostic_fee_collected')) {
                $case->forceFill(['diagnostic_fee_collected_at' => now()])->save();
            }

            return $case;
        });

        flash('Warranty case ' . $case->fresh()->case_number . ' created with linked ticket '
            . $case->serviceTicket->ticket_number . '.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}

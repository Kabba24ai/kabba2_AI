<?php

namespace Database\Seeders\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use Illuminate\Database\Seeder;

/**
 * Demo data for the Service Operations dashboard — LOCAL/DEV ONLY.
 * Run manually: php artisan db:seed --class="Database\Seeders\Service\ServiceTicketSeeder"
 */
class ServiceTicketSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'development'])) {
            $this->command?->warn('ServiceTicketSeeder only runs in local/dev environments. Skipped.');
            return;
        }

        $employees = User::active()->limit(6)->get();
        $equipment = Equipment::limit(8)->get();

        $mk = function (array $attrs, array $personnelIdx = []) use ($employees, $equipment) {
            $ticket = ServiceTicket::create(array_merge([
                'service_location'         => ServiceLocation::InShop->value,
                'financial_responsibility' => FinancialResponsibility::InternalExpense->value,
                'financial_status'         => FinancialStatus::NotBillable->value,
                'equipment_id'             => $equipment->isNotEmpty() ? $equipment->random()->id : null,
            ], $attrs));

            $ids = collect($personnelIdx)
                ->map(fn ($i) => $employees->get($i)?->id)
                ->filter()
                ->all();
            if ($ids) {
                $ticket->personnel()->sync($ids);
            }

            return $ticket;
        };

        // ── Active queue spread ───────────────────────────────────────
        $mk([
            'service_type'  => ServiceType::CustomerDamageRepair->value,
            'priority'      => ServicePriority::Emergency->value,
            'repair_status' => RepairStatus::InProgress->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::NotBillable->value,
            'opened_at'     => now()->subDays(1),
            'description'   => 'Hydraulic line blown on rental return.',
        ], [0, 1]);

        // Emergency with NO personnel — drives the "no technician" alert
        $mk([
            'service_type'  => ServiceType::FieldServiceCall->value,
            'service_location' => ServiceLocation::CustomerSite->value,
            'priority'      => ServicePriority::Emergency->value,
            'repair_status' => RepairStatus::Open->value,
            'opened_at'     => now()->subHours(4),
            'description'   => 'Machine down on active jobsite.',
        ]);

        $mk([
            'service_type'  => ServiceType::InspectionDiagnosis->value,
            'priority'      => ServicePriority::High->value,
            'repair_status' => RepairStatus::Diagnosing->value,
            'opened_at'     => now()->subDays(3),
        ], [2]);

        $mk([
            'service_type'  => ServiceType::InternalRepair->value,
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::Open->value,
            'opened_at'     => now()->subDays(8),
        ], [3]);

        $mk([
            'service_type'  => ServiceType::CustomerDamageRepair->value,
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::ReadyForPickup->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::ReadyToBill->value,
            'opened_at'     => now()->subDays(6),
        ], [1]);

        $mk([
            'service_type'  => ServiceType::InternalRepair->value,
            'priority'      => ServicePriority::Low->value,
            'repair_status' => RepairStatus::InProgress->value,
            'opened_at'     => now()->subDays(2),
        ], [4]);

        // ── Blocked queue spread ──────────────────────────────────────
        // Emergency stuck on parts for two weeks — proves the blocking rule
        $mk([
            'service_type'  => ServiceType::CustomerDamageRepair->value,
            'priority'      => ServicePriority::Emergency->value,
            'repair_status' => RepairStatus::WaitingOnParts->value,
            'blocked_reason'=> 'Final drive on backorder from OEM',
            'expected_action_date' => now()->addDays(4)->toDateString(),
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'opened_at'     => now()->subDays(14),
        ], [0]);

        // Past-due expected action date — drives the overdue alert
        $mk([
            'service_type'  => ServiceType::OemWarrantyRepair->value,
            'priority'      => ServicePriority::High->value,
            'repair_status' => RepairStatus::WaitingOnWarrantyApproval->value,
            'blocked_reason'=> 'Claim #W-2214 with OEM',
            'expected_action_date' => now()->subDays(2)->toDateString(),
            'financial_responsibility' => FinancialResponsibility::OemWarranty->value,
            'financial_status'         => FinancialStatus::WarrantyWaitingApproval->value,
            'opened_at'     => now()->subDays(10),
        ], [2, 3]);

        // Long wait on customer approval — drives the waiting-too-long alert
        $mk([
            'service_type'  => ServiceType::CustomerDamageRepair->value,
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::WaitingOnCustomerApproval->value,
            'blocked_reason'=> 'Estimate sent, awaiting customer sign-off',
            'expected_action_date' => now()->addDays(1)->toDateString(),
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'opened_at'     => now()->subDays(7),
        ], [1]);

        // Warranty approved but repair still waiting — drives the resume alert
        $mk([
            'service_type'  => ServiceType::OemWarrantyRepair->value,
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::WaitingOnWarrantyApproval->value,
            'blocked_reason'=> 'Approval received, awaiting parts release',
            'expected_action_date' => now()->addDays(7)->toDateString(),
            'financial_responsibility' => FinancialResponsibility::OemWarranty->value,
            'financial_status'         => FinancialStatus::WarrantyApproved->value,
            'opened_at'     => now()->subDays(5),
        ], [4]);

        // ── Financial spread ──────────────────────────────────────────
        $mk([
            'service_type'  => ServiceType::CustomerDamageRepair->value,
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::Completed->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::ChargeCreated->value,
            'opened_at'     => now()->subDays(12),
            'completed_at'  => now()->subDays(2),
        ], [0]);

        $mk([
            'service_type'  => ServiceType::CustomerDamageRepair->value,
            'priority'      => ServicePriority::Normal->value,
            'repair_status' => RepairStatus::Closed->value,
            'financial_responsibility' => FinancialResponsibility::CustomerPay->value,
            'financial_status'         => FinancialStatus::Paid->value,
            'opened_at'     => now()->subDays(20),
            'completed_at'  => now()->subDays(9),
            'closed_at'     => now()->subDays(8),
        ], [1]);

        // ── Field dispatch timing rows for the active field call ─────
        $fieldTicket = ServiceTicket::where('service_type', ServiceType::FieldServiceCall->value)
            ->whereIn('repair_status', RepairStatus::notFinished())
            ->first();

        if ($fieldTicket && $employees->isNotEmpty()) {
            $fieldTicket->resourceDispatches()->create([
                'employee_id'      => $employees->first()->id,
                'vehicle_id'       => 1,
                'departed_shop_at' => now()->subHours(2),
                'arrived_on_site_at' => now()->subHour(),
            ]);
            if ($employees->count() > 1) {
                $fieldTicket->resourceDispatches()->create([
                    'employee_id'      => $employees->get(1)->id,
                    'trailer_id'       => 1,
                    'departed_shop_at' => now()->subHours(2),
                ]);
            }
        }

        $this->command?->info('Seeded ' . ServiceTicket::count() . ' service tickets with demo data.');
    }
}

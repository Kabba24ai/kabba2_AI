<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Helpers\CustomHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixRunningBalancesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $results = [];

        $customers = Customer::query()
            ->whereHas('accounts')
            ->get();

        foreach ($customers as $customer) {

            DB::beginTransaction();

            try {

                // =========================
                // BEFORE DATA
                // =========================

                $beforeAccounts = CustomerAccount::where('customer_id', $customer->id)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => $row->id,
                            'type' => $row->type,
                            'amount' => $row->amount,
                            'balance' => $row->balance,
                        ];
                    });

                $beforeCustomerBalance = $customer->available_credit_balance;

                // =========================
                // FIX
                // =========================

                CustomHelper::fixTheRunningBalance($customer->id);

                // refresh customer
                $customer->refresh();

                // =========================
                // AFTER DATA
                // =========================

                $afterAccounts = CustomerAccount::where('customer_id', $customer->id)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => $row->id,
                            'type' => $row->type,
                            'amount' => $row->amount,
                            'balance' => $row->balance,
                        ];
                    });

                $afterCustomerBalance = $customer->available_credit_balance;

                $isCorrupted = (float)$beforeCustomerBalance !== (float)$afterCustomerBalance;

                $balanceDifference = round(
                    (float)$afterCustomerBalance - (float)$beforeCustomerBalance,
                    2
                );

                // =========================
                // DETECT CHANGES
                // =========================

                $changed = [];

                foreach ($beforeAccounts as $index => $beforeRow) {

                    $afterRow = $afterAccounts[$index] ?? null;

                    if (!$afterRow) {
                        continue;
                    }

                    if ((float)$beforeRow['balance'] !== (float)$afterRow['balance']) {

                        $changed[] = [
                            'transaction_id' => $beforeRow['id'],
                            'type' => $beforeRow['type'],

                            'before_balance' => $beforeRow['balance'],
                            'after_balance' => $afterRow['balance'],
                        ];
                    }
                }

                // =========================
                // LOG
                // =========================

                Log::channel('daily')->info('Customer Running Balance Fixed', [

                    'customer_id' => $customer->id,
                    'customer_name' => $customer->company_name,

                    'before_customer_balance' => $beforeCustomerBalance,
                    'after_customer_balance' => $afterCustomerBalance,

                    'is_corrupted' => $isCorrupted,
                    'was_fixed' => $isCorrupted,

                    'difference' => $balanceDifference,

                    'changes_count' => count($changed),

                    'changes' => $changed,
                ]);

                DB::commit();

                $results[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->company_name,

                'before_balance' => $beforeCustomerBalance,
                'after_balance' => $afterCustomerBalance,

                'is_corrupted' => $isCorrupted,
                'was_fixed' => $isCorrupted,

                'difference' => $balanceDifference,

                'changes_count' => count($changed),

                'status' => $isCorrupted
                    ? 'CORRUPTED_AND_FIXED'
                    : 'OK',
            ];

            } catch (\Throwable $e) {

                DB::rollBack();

                Log::error('Running Balance Fix Failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->company_name,
                    'fixed' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'customers_checked' => count($results),
            'results' => $results,
        ]);
    }
}
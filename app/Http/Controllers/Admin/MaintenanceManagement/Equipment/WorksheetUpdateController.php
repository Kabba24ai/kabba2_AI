<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorksheetUpdateController extends Controller
{
    public function __invoke(Request $request)
    {
        $userId = Auth::id();

        $rows = $request->input('rows', []);

        if (empty($rows) || !is_array($rows)) {
            return redirect()
                ->back()
                ->with('error', 'No worksheet rows were submitted.');
        }

        $equipments = Equipment::whereIn('unique_id', array_keys($rows))->get()->keyBy('unique_id');

        $validatedRows = [];
        $errors = [];

        foreach ($rows as $uniqueId => $row) {
            $equipment = $equipments->get($uniqueId);

            if (!$equipment) {
                $errors["rows.$uniqueId"][] = 'Equipment row was not found. Please refresh and try again.';
                continue;
            }

            $rowData = array_merge([
                'equipment_name' => $equipment->equipment_name,
                'equipment_id' => $equipment->equipment_id,
                'product_category_id' => $equipment->product_category_id,
                'store_id' => $equipment->store_id,
                'equipment_hours' => $equipment->equipment_hours,
                'overage_rate' => $equipment->overage_rate,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'model_year' => $equipment->model_year,
                'date_acquired' => $equipment->date_acquired,
                'key_starting_mechanism' => $equipment->key_starting_mechanism?->value ?? $equipment->key_starting_mechanism,
                'equipment_value' => $equipment->equipment_value,
                'purchase_cost' => $equipment->purchase_cost,
                'freight_shipping' => $equipment->freight_shipping,
                'taxes_fees' => $equipment->taxes_fees,
                'ownership_type' => $equipment->ownership_type,
                'down_payment' => $equipment->down_payment,
                'amount_financed' => $equipment->amount_financed,
                'finance_company' => $equipment->finance_company,
                'term_in_months' => $equipment->term_in_months,
                'interest_rate' => $equipment->interest_rate,
                'monthly_payment' => $equipment->monthly_payment,
                'vehicle_identification_number' => $equipment->vehicle_identification_number,
                'serial_number' => $equipment->serial_number,
                'license_plate' => $equipment->license_plate,
                'imei' => $equipment->imei,
                'warranty_duration_months' => $equipment->warranty_duration_months,
                'warranty_duration_hours' => $equipment->warranty_duration_hours,
                'coi_submitted' => $equipment->coi_submitted,
                'not_for_rent' => (int) $equipment->not_for_rent,
                'checklist_master_id' => $equipment->checklist_master_id,
                'parts_list_id' => $equipment->parts_list_id,
            ], Arr::only((array) $row, [
                'equipment_name',
                'equipment_id',
                'product_category_id',
                'store_id',
                'equipment_hours',
                'overage_rate',
                'brand',
                'model',
                'model_year',
                'date_acquired',
                'key_starting_mechanism',
                'equipment_value',
                'purchase_cost',
                'freight_shipping',
                'taxes_fees',
                'ownership_type',
                'down_payment',
                'amount_financed',
                'finance_company',
                'term_in_months',
                'interest_rate',
                'monthly_payment',
                'vehicle_identification_number',
                'serial_number',
                'license_plate',
                'imei',
                'warranty_duration_months',
                'warranty_duration_hours',
                'coi_submitted',
                'not_for_rent',
                'checklist_master_id',
                'parts_list_id',
            ]));

            if (!empty($rowData['date_acquired'])) {
                $timestamp = strtotime((string) $rowData['date_acquired']);
                $rowData['date_acquired'] = $timestamp ? date('Y-m-d', $timestamp) : $rowData['date_acquired'];
            }

            if (!empty($rowData['coi_submitted'])) {
                $timestamp = strtotime((string) $rowData['coi_submitted']);
                $rowData['coi_submitted'] = $timestamp ? date('Y-m-d', $timestamp) : $rowData['coi_submitted'];
            }

            if ($rowData['overage_rate'] === '') {
                $rowData['overage_rate'] = null;
            }

            $validator = Validator::make($rowData, [
                'equipment_name' => ['nullable', 'string', 'max:255'],
                'equipment_id' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('equipment', 'equipment_id')->ignore($equipment->id),
                ],
                'product_category_id' => ['nullable', 'exists:product_categories,id'],
                'store_id' => ['nullable', 'exists:stores,id'],
                'equipment_hours' => ['nullable', 'numeric', 'min:0'],
                'overage_rate' => ['nullable', 'numeric', 'min:0'],
                'brand' => ['nullable', 'string', 'max:255'],
                'model' => ['nullable', 'string', 'max:255'],
                'model_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
                'date_acquired' => ['nullable', 'date'],
                'key_starting_mechanism' => ['nullable', Rule::in(['none', '1_key', '2_keys', 'key_pad', 'pull_cord'])],
                'equipment_value' => ['nullable', 'numeric', 'min:0'],
                'purchase_cost' => ['nullable', 'numeric', 'min:0'],
                'freight_shipping' => ['nullable', 'numeric', 'min:0'],
                'taxes_fees' => ['nullable', 'numeric', 'min:0'],
                'ownership_type' => ['nullable', Rule::in(['owned', 'financed', 'leased'])],
                'down_payment' => ['nullable', 'numeric', 'min:0'],
                'amount_financed' => ['nullable', 'numeric', 'min:0'],
                'finance_company' => ['nullable', 'string', 'max:255'],
                'term_in_months' => ['nullable', 'integer', 'min:1'],
                'interest_rate' => ['nullable', 'numeric', 'min:0'],
                'monthly_payment' => ['nullable', 'numeric', 'min:0'],
                'vehicle_identification_number' => ['nullable', 'string', 'max:255'],
                'serial_number' => ['nullable', 'string', 'max:255'],
                'license_plate' => ['nullable', 'string', 'max:255'],
                'imei' => ['nullable', 'string', 'max:255'],
                'warranty_duration_months' => ['nullable', 'integer', 'min:0'],
                'warranty_duration_hours' => ['nullable', 'integer', 'min:0'],
                'coi_submitted' => ['nullable', 'date'],
                'not_for_rent' => ['nullable', 'boolean'],
                'checklist_master_id' => ['nullable', 'exists:checklist_masters,id'],
                'parts_list_id' => ['nullable', 'exists:parts_lists,id'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $errors["rows.$uniqueId.$field"] = $messages;
                }
                continue;
            }

            $validatedRows[$uniqueId] = $validator->validated();
        }

        if (!empty($errors)) {
            return redirect()
                ->back()
                ->withErrors($errors)
                ->withInput()
                ->with('error', 'Some rows contain invalid data. Please fix highlighted values and save again.');
        }

        DB::transaction(function () use ($validatedRows, $equipments, $userId) {
            foreach ($validatedRows as $uniqueId => $data) {
                $equipment = $equipments->get($uniqueId);

                $isTracked = $data['overage_rate'] !== null && $data['overage_rate'] !== '';

                $equipment->update([
                    'equipment_name' => $data['equipment_name'],
                    'equipment_id' => $data['equipment_id'],
                    'product_category_id' => $data['product_category_id'],
                    'store_id' => $data['store_id'] ?: null,
                    'equipment_hours' => $data['equipment_hours'],
                    'overage_rate' => $data['overage_rate'],
                    'is_tracked' => $isTracked ? 'Yes' : 'No',
                    'brand' => $data['brand'],
                    'model' => $data['model'],
                    'model_year' => $data['model_year'],
                    'date_acquired' => $data['date_acquired'],
                    'key_starting_mechanism' => $data['key_starting_mechanism'],
                    'equipment_value' => $data['equipment_value'],
                    'purchase_cost' => $data['purchase_cost'],
                    'freight_shipping' => $data['freight_shipping'],
                    'taxes_fees' => $data['taxes_fees'],
                    'ownership_type' => $data['ownership_type'],
                    'down_payment' => $data['down_payment'],
                    'amount_financed' => $data['amount_financed'],
                    'finance_company' => $data['finance_company'],
                    'term_in_months' => $data['term_in_months'],
                    'interest_rate' => $data['interest_rate'],
                    'monthly_payment' => $data['monthly_payment'],
                    'vehicle_identification_number' => $data['vehicle_identification_number'],
                    'serial_number' => $data['serial_number'],
                    'license_plate' => $data['license_plate'],
                    'imei' => $data['imei'],
                    'warranty_duration_months' => $data['warranty_duration_months'],
                    'warranty_duration_hours' => $data['warranty_duration_hours'],
                    'coi_submitted' => $data['coi_submitted'],
                    'not_for_rent' => ((int) $data['not_for_rent']) === 1 ? 1 : 0,
                    'checklist_master_id' => $data['checklist_master_id'] ?: null,
                    'parts_list_id' => $data['parts_list_id'] ?: null,
                    'updated_by' => $userId,
                ]);
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Equipment worksheet updated successfully.');
    }
}

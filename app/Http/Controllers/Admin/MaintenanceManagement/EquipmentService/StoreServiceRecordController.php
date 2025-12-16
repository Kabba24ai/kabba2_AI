<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\EquipmentService\StoreServiceRecordRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreServiceRecordController extends Controller
{
    public function __invoke(StoreServiceRecordRequest $request)
    {
        try {
            // Convert date format from m/d/Y to Y-m-d for database (handle nullable checked_date)
            $performedDate = Carbon::createFromFormat('m/d/Y', $request->performed_date)->format('Y-m-d');
            $checkedDate = $request->filled('checked_date') ? Carbon::createFromFormat('m/d/Y', $request->checked_date)->format('Y-m-d') : null;

            $data = [
                'equipment_id' => $request->equipment_id,
                'service_template_id' => $request->service_template_id,
                'service_task_id' => $request->service_task_id,
                'interval_value' => $request->interval_value,
                'interval_type' => $request->interval_type,
                'performed_by' => $request->performed_by,
                'performed_date' => $performedDate,
                'checked_by' => $request->filled('checked_by') ? $request->checked_by : null,
                'checked_date' => $checkedDate,
                'actual_hours' => $request->actual_hours,
                'notes' => $request->notes,
                'updated_at' => now(),
            ];

            // Check if this is an update (record_id provided) or create
            if ($request->has('record_id') && $request->record_id) {
                // Update existing record
                DB::table('equipment_service_tasks')
                    ->where('id', $request->record_id)
                    ->update($data);
                
                $message = 'Service record updated successfully';
                $recordId = $request->record_id;
            } else {
                // Create new record
                $data['created_at'] = now();
                $recordId = DB::table('equipment_service_tasks')->insertGetId($data);
                
                $message = 'Service record saved successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'record_id' => $recordId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save service record: ' . $e->getMessage(),
            ], 500);
        }
    }
}

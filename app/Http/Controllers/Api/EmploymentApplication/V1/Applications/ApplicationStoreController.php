<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Applications;

use App\Http\Controllers\Api\BaseController;
use App\Models\Locations\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Stores\Application;
use App\Helpers\MediaHelper;
use Illuminate\Support\Str;


class ApplicationStoreController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
        {

            $data = $request->all(); 
            // Log::info('Application form data', $data);

            // ---- STEP 1: Normalize arrays ----
            $positions = json_decode($request->positions ?? '[]', true);
            $availableDays = json_decode($request->available_days ?? '[]', true);
            $equipmentRepair = json_decode($request->equipment_repair ?? '[]', true);
            $equipmentOperated = json_decode($request->equipment_operated ?? '[]', true);
            $trailerExperience = json_decode($request->trailer_experience ?? '[]', true);
            $computerSkills = json_decode($request->computer_skills ?? '[]', true);
            $systemsUsed = json_decode($request->systems_used ?? '[]', true);

            // ---- STEP 2: Create Application ----
            $application = Application::create([
                'status' => 0,
                'store_id' => $request->store_id,

                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'start_date' => $request->start_date,

                // JSON GROUPS
                'personal_details' => [
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip_code' => $request->zip_code,
                ],

                'job_preferences' => [
                    'location_flexibility' => $request->location_flexibility,
                    'desired_pay' => $request->desired_pay,
                    'positions' => $positions,
                    'available_days' => $availableDays,
                ],

                'experience_details' => [
                    'reliable_transportation' => (bool) $request->reliable_transportation,
                    'work_experience' => $request->work_experience,
                    'mechanical_experience' => $request->mechanical_experience,
                ],

                'skills' => [
                    'equipment_repair' => $equipmentRepair,
                    'equipment_operated' => $equipmentOperated,
                    'computer_skills' => $computerSkills,
                    'systems_used' => $systemsUsed,
                ],

                'driving_details' => [
                    'diagnostic_ability' => $request->diagnostic_ability,
                    'hydraulics_comfort' => $request->hydraulics_comfort,
                    'equipment_care' => $request->equipment_care,
                    'customer_facing' => $request->customer_facing,
                    'drug_test_consent' => (bool) $request->drug_test_consent,
                    'license_type' => $request->license_type,
                    'license_state' => $request->license_state,
                    'moving_violations' => $request->moving_violations,
                    'dui_dwi' => $request->dui_dwi,
                    'license_suspended' => $request->license_suspended,
                    'can_be_insured' => $request->can_be_insured,
                    'driving_notes' => $request->driving_notes,
                ],
            ]);

            // ---- STEP 3: Upload resume using MediaHelper ----
            if ($request->hasFile('resume')) {
                $upload = MediaHelper::uploadStorageFile(
                    'Secure Asset',
                    $request->file('resume'),
                    'applications/resumes',
                    $application
                );

                if (!empty($upload['mediaObj'])) {
                    $application->resume_media_id = $upload['mediaObj']->id;
                    $application->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $application->id,
                'unique_id' => $application->unique_id,
            ]);
        }

}

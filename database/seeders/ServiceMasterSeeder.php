<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceCategory;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplateTask;

class ServiceMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Create default categories
        $preventive = ServiceCategory::create([
            'name' => 'Preventive Maintenance',
            'description' => 'Regular maintenance tasks to prevent equipment failure',
            'color' => '#10B981'
        ]);

        $safety = ServiceCategory::create([
            'name' => 'Safety Inspections',
            'description' => 'Safety-related checks and inspections',
            'color' => '#F59E0B'
        ]);

        $repairs = ServiceCategory::create([
            'name' => 'Repairs',
            'description' => 'Equipment repair and fix tasks',
            'color' => '#EF4444'
        ]);

        $calibration = ServiceCategory::create([
            'name' => 'Calibration',
            'description' => 'Equipment calibration and adjustment tasks',
            'color' => '#8B5CF6'
        ]);

        // Create sample tasks
        $tasks = [
            // Preventive Maintenance
            [
                'name' => 'Oil Change',
                'description' => 'Change engine oil and filter',
                'category_id' => $preventive->id,
                'auto_apply' => true,
                'instructions' => '1. Warm up engine\n2. Drain old oil\n3. Replace oil filter\n4. Add new oil\n5. Check oil level'
            ],
            [
                'name' => 'Hydraulic Fluid Check',
                'description' => 'Check and top up hydraulic fluid levels',
                'category_id' => $preventive->id,
                'auto_apply' => true,
                'instructions' => '1. Check fluid level with engine off\n2. Top up if below minimum\n3. Check for leaks\n4. Test hydraulic functions'
            ],
            [
                'name' => 'Air Filter Replacement',
                'description' => 'Replace air filter element',
                'category_id' => $preventive->id,
                'auto_apply' => false,
                'instructions' => '1. Remove old filter\n2. Clean housing\n3. Install new filter\n4. Ensure proper seal'
            ],
            
            // Safety Inspections
            [
                'name' => 'Safety Light Inspection',
                'description' => 'Check all safety lights and beacons',
                'category_id' => $safety->id,
                'auto_apply' => true,
                'instructions' => '1. Test all warning lights\n2. Check beacon functionality\n3. Inspect lens condition\n4. Test emergency stop'
            ],
            [
                'name' => 'Seatbelt & ROPS Check',
                'description' => 'Inspect seatbelts and ROPS structure',
                'category_id' => $safety->id,
                'auto_apply' => true,
                'instructions' => '1. Check seatbelt condition\n2. Test buckle mechanism\n3. Inspect ROPS for damage\n4. Check mounting bolts'
            ],
            
            // Repairs
            [
                'name' => 'Track Repair',
                'description' => 'Repair damaged tracks',
                'category_id' => $repairs->id,
                'auto_apply' => false,
                'instructions' => '1. Assess damage\n2. Remove damaged sections\n3. Install replacement parts\n4. Test functionality'
            ],
            
            // Calibration
            [
                'name' => 'Load Chart Verification',
                'description' => 'Verify load chart accuracy and calibration',
                'category_id' => $calibration->id,
                'auto_apply' => false,
                'instructions' => '1. Set up test weights\n2. Check display accuracy\n3. Calibrate if necessary\n4. Document results'
            ]
        ];

        foreach ($tasks as $taskData) {
            ServiceTask::create($taskData);
        }

        // Create sample templates
        $weeklyTemplate = ServiceTemplate::create([
            'name' => 'Weekly Maintenance',
            'description' => 'Weekly maintenance checklist for heavy equipment',
            'category_id' => $preventive->id
        ]);

        $monthlyTemplate = ServiceTemplate::create([
            'name' => 'Monthly Safety Check',
            'description' => 'Monthly safety inspection template',
            'category_id' => $safety->id
        ]);

        // Associate tasks with templates
        $weeklyTasks = ServiceTask::whereIn('name', ['Hydraulic Fluid Check', 'Safety Light Inspection'])->get();
        foreach ($weeklyTasks as $index => $task) {
            ServiceTemplateTask::create([
                'template_id' => $weeklyTemplate->id,
                'task_id' => $task->id,
                'sort_order' => $index
            ]);
        }

        $monthlyTasks = ServiceTask::whereIn('name', ['Oil Change', 'Air Filter Replacement', 'Seatbelt & ROPS Check'])->get();
        foreach ($monthlyTasks as $index => $task) {
            ServiceTemplateTask::create([
                'template_id' => $monthlyTemplate->id,
                'task_id' => $task->id,
                'sort_order' => $index
            ]);
        }
    }
}
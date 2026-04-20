<?php

namespace Database\Seeders;

use App\Models\Global\AIAssignmentRule;
use Illuminate\Database\Seeder;

class AIAssignmentRuleSeeder extends Seeder
{
    public function run(): void
    {
        AIAssignmentRule::query()->updateOrCreate(
            [
                'product_name' => 'Mini Skid Heavy Duty',
                'equipment_name' => 'CTX100',
            ],
            [
                'relationship_type' => 'primary',
                'actions_required' => [],
                'notes' => 'Primary assignment for heavy-duty mini skid orders.',
                'active' => true,
            ]
        );

        AIAssignmentRule::query()->updateOrCreate(
            [
                'product_name' => 'Mini Skid Heavy Duty',
                'equipment_name' => 'CTX160',
            ],
            [
                'relationship_type' => 'upgrade',
                'actions_required' => ['notify_internal'],
                'notes' => 'Approved upgrade option.',
                'active' => true,
            ]
        );

        AIAssignmentRule::query()->updateOrCreate(
            [
                'product_name' => 'Mini Skid Heavy Duty',
                'equipment_name' => 'Wacker SM100',
            ],
            [
                'relationship_type' => 'downgrade',
                'actions_required' => [
                    'manager_review',
                    'customer_contact',
                    'customer_approval_required',
                ],
                'notes' => 'Allowed downgrade only with review and customer approval.',
                'active' => true,
            ]
        );
    }
}

